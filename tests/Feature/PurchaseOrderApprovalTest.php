<?php

namespace Tests\Feature;

use App\Models\ChefRequisition;
use App\Models\Entity;
use App\Models\EntityIntegrationSetting;
use App\Models\EntityNotificationSetting;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseOrderApprovalTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function manager_can_approve_purchase_order_and_notify_purchasing_team(): void
    {
        Mail::fake();
        Http::fake([
            'https://api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201),
        ]);

        $entity = Entity::create([
            'name' => 'Test Restaurant',
            'slug' => 'test-restaurant',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        EntityNotificationSetting::create([
            'entity_id' => $entity->id,
            'notify_requisitions' => true,
            'notify_expenses' => true,
            'notify_purchase_orders' => true,
            'notify_payroll' => false,
            'notify_email_daily' => false,
            'sms_enabled' => true,
            'whatsapp_enabled' => true,
            'sms_provider' => 'twilio',
            'notification_channels' => ['email', 'whatsapp'],
            'purchase_order_notification_emails' => ['purchasing@example.com'],
            'purchase_order_notification_phones' => ['+15550007777'],
        ]);

        EntityIntegrationSetting::create([
            'entity_id' => $entity->id,
            'twilio_account_sid' => 'AC123456789',
            'twilio_auth_token' => 'secret',
            'twilio_sms_number' => '+15550008888',
            'twilio_whatsapp_number' => '+15550009999',
            'twilio_whatsapp_enabled' => true,
            'twilio_sms_enabled' => true,
        ]);

        $vendor = Vendor::create([
            'name' => 'Fresh Farms',
            'email' => 'orders@freshfarms.test',
            'phone' => '+15550123456',
            'is_active' => true,
        ]);

        $chef = User::factory()->create(['entity_id' => $entity->id]);

        $requisition = ChefRequisition::create([
            'chef_id' => $chef->id,
            'requested_for_date' => now()->addDays(2),
            'items' => [[
                'item' => 'Tomatoes',
                'vendor' => $vendor->name,
                'quantity' => 10,
                'uom' => 'kg',
                'price' => 4.5,
            ]],
            'status' => 'approved',
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-TEST-0001',
            'requisition_id' => $requisition->id,
            'created_by' => $chef->id,
            'items' => [[
                'item' => 'Tomatoes',
                'vendor' => $vendor->name,
                'quantity' => 10,
                'uom' => 'kg',
                'price' => 4.5,
            ]],
            'total_quantity' => 10,
            'subtotal' => 45,
            'tax' => 0,
            'other_charges' => 0,
            'grand_total' => 45,
            'status' => 'open',
            'workflow_status' => 'pending',
            'notes' => null,
        ]);

        $approvePermission = Permission::firstOrCreate(['name' => 'approve purchase orders', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'send purchase orders', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'mark purchased', 'guard_name' => 'web']);

        $role = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $role->syncPermissions([$approvePermission]);

        $manager = User::factory()->create(['entity_id' => $entity->id]);
        $manager->assignRole($role);

        $response = $this->actingAs($manager)->post(route('purchase-orders.approve', $purchaseOrder));

        $response->assertRedirect(route('purchase-orders.show', $purchaseOrder));
        $response->assertSessionHas('success');

        $purchaseOrder->refresh();

        $this->assertEquals('approved', $purchaseOrder->workflow_status);
        $this->assertSame($manager->id, $purchaseOrder->approved_by);
        $this->assertNotNull($purchaseOrder->approved_at);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/Messages.json')
            && in_array($request['To'], ['+15550007777', 'whatsapp:+15550007777'], true)
            && str_contains($request['Body'], 'PO PO-TEST-0001 has been approved');
        });
    }

    #[Test]
    public function users_without_permission_cannot_approve_purchase_orders(): void
    {
        $entity = Entity::create([
            'name' => 'Second Restaurant',
            'slug' => 'second-restaurant',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $chef = User::factory()->create(['entity_id' => $entity->id]);

        $requisition = ChefRequisition::create([
            'chef_id' => $chef->id,
            'requested_for_date' => now()->addDay(),
            'items' => [[
                'item' => 'Peppers',
                'vendor' => 'Generic Vendor',
                'quantity' => 5,
                'uom' => 'kg',
                'price' => 3.2,
            ]],
            'status' => 'approved',
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-LOCKED-01',
            'requisition_id' => $requisition->id,
            'created_by' => $chef->id,
            'items' => [[
                'item' => 'Peppers',
                'vendor' => 'Generic Vendor',
                'quantity' => 5,
                'uom' => 'kg',
                'price' => 3.2,
            ]],
            'total_quantity' => 5,
            'subtotal' => 16,
            'tax' => 0,
            'other_charges' => 0,
            'grand_total' => 16,
            'status' => 'open',
            'workflow_status' => 'pending',
        ]);

        $user = User::factory()->create(['entity_id' => $entity->id]);

        $response = $this->actingAs($user)->post(route('purchase-orders.approve', $purchaseOrder));

        $response->assertForbidden();
    }

    #[Test]
    public function manager_marks_purchase_order_completed_after_purchaser_sends(): void
    {
        $entity = Entity::create([
            'name' => 'Purchasing Restaurant',
            'slug' => 'purchasing-restaurant',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $chef = User::factory()->create(['entity_id' => $entity->id]);

        $requisition = ChefRequisition::create([
            'chef_id' => $chef->id,
            'requested_for_date' => now()->addDay(),
            'items' => [[
                'item' => 'Flour',
                'vendor' => 'Bulk Vendor',
                'quantity' => 2,
                'uom' => 'bag',
                'price' => 25,
            ]],
            'status' => 'approved',
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-PURCHASED-01',
            'requisition_id' => $requisition->id,
            'created_by' => $chef->id,
            'items' => [[
                'item' => 'Flour',
                'vendor' => 'Bulk Vendor',
                'quantity' => 2,
                'uom' => 'bag',
                'price' => 25,
            ]],
            'total_quantity' => 2,
            'subtotal' => 50,
            'tax' => 0,
            'other_charges' => 0,
            'grand_total' => 50,
            'status' => 'open',
            'workflow_status' => 'approved',
        ]);

        $viewPermission = Permission::firstOrCreate(['name' => 'view purchase orders', 'guard_name' => 'web']);
        $sendPermission = Permission::firstOrCreate(['name' => 'send purchase orders', 'guard_name' => 'web']);
        $markPermission = Permission::firstOrCreate(['name' => 'mark purchased', 'guard_name' => 'web']);
        $approvePermission = Permission::firstOrCreate(['name' => 'approve purchase orders', 'guard_name' => 'web']);

        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $managerRole->syncPermissions([$approvePermission, $markPermission, $viewPermission]);

        $purchaserRole = Role::firstOrCreate(['name' => 'purchaser', 'guard_name' => 'web']);
        $purchaserRole->syncPermissions([$sendPermission, $viewPermission]);

        $manager = User::factory()->create(['entity_id' => $entity->id]);
        $manager->assignRole($managerRole);

        $purchaser = User::factory()->create(['entity_id' => $entity->id]);
        $purchaser->assignRole($purchaserRole);

        $sendResponse = $this->actingAs($purchaser)->post(route('purchase-orders.send', $purchaseOrder));

        $sendResponse->assertRedirect(route('purchase-orders.show', $purchaseOrder))
            ->assertSessionHas('success', 'PO sent to vendors successfully.');

        $purchaseOrder->refresh();
        $this->assertSame('open', $purchaseOrder->status);
        $this->assertSame('sent_to_vendor', $purchaseOrder->workflow_status);

        $response = $this->actingAs($manager)->post(route('purchase-orders.complete', $purchaseOrder));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Purchase order marked as completed.');

        $purchaseOrder->refresh();

        $this->assertSame('closed', $purchaseOrder->status);
        $this->assertSame('completed', $purchaseOrder->workflow_status);
        $this->assertNotNull($purchaseOrder->purchased_at);

        $this->assertDatabaseHas('expenses', [
            'purchase_order_id' => $purchaseOrder->id,
            'amount' => 50,
        ]);
    }

    #[Test]
    public function manager_can_only_complete_after_sending_to_vendor(): void
    {
        $entity = Entity::create([
            'name' => 'Direct Complete Diner',
            'slug' => 'direct-complete-diner',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $chef = User::factory()->create(['entity_id' => $entity->id]);

        $requisition = ChefRequisition::create([
            'chef_id' => $chef->id,
            'requested_for_date' => now()->addDay(),
            'items' => [[
                'item' => 'Olive Oil',
                'vendor' => 'Mediterranean Supplies',
                'quantity' => 4,
                'uom' => 'bottle',
                'price' => 18,
            ]],
            'status' => 'approved',
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-QUICK-APPROVED',
            'requisition_id' => $requisition->id,
            'created_by' => $chef->id,
            'items' => [[
                'item' => 'Olive Oil',
                'vendor' => 'Mediterranean Supplies',
                'quantity' => 4,
                'uom' => 'bottle',
                'price' => 18,
            ]],
            'total_quantity' => 4,
            'subtotal' => 72,
            'tax' => 0,
            'other_charges' => 0,
            'grand_total' => 72,
            'status' => 'open',
            'workflow_status' => 'approved',
        ]);

        $viewPermission = Permission::firstOrCreate(['name' => 'view purchase orders', 'guard_name' => 'web']);
        $sendPermission = Permission::firstOrCreate(['name' => 'send purchase orders', 'guard_name' => 'web']);
        $markPermission = Permission::firstOrCreate(['name' => 'mark purchased', 'guard_name' => 'web']);
        $approvePermission = Permission::firstOrCreate(['name' => 'approve purchase orders', 'guard_name' => 'web']);

        $managerRole = Role::firstOrCreate(['name' => 'manager-workflow', 'guard_name' => 'web']);
        $managerRole->syncPermissions([$approvePermission, $markPermission, $viewPermission]);

        $purchaserRole = Role::firstOrCreate(['name' => 'purchaser-workflow', 'guard_name' => 'web']);
        $purchaserRole->syncPermissions([$sendPermission, $viewPermission]);

        $manager = User::factory()->create(['entity_id' => $entity->id]);
        $manager->assignRole($managerRole);

        $purchaser = User::factory()->create(['entity_id' => $entity->id]);
        $purchaser->assignRole($purchaserRole);

        $response = $this->actingAs($manager)->post(route('purchase-orders.complete', $purchaseOrder));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Send this purchase order to the vendor before marking it as completed.');

        $purchaseOrder->refresh();
        $this->assertSame('approved', $purchaseOrder->workflow_status);
        $this->assertNull($purchaseOrder->purchased_at);

        $sendResponse = $this->actingAs($purchaser)->post(route('purchase-orders.send', $purchaseOrder));

        $sendResponse->assertRedirect(route('purchase-orders.show', $purchaseOrder))
            ->assertSessionHas('success', 'PO sent to vendors successfully.');

        $purchaseOrder->refresh();
        $this->assertSame('sent_to_vendor', $purchaseOrder->workflow_status);

        $completeResponse = $this->actingAs($manager)->post(route('purchase-orders.complete', $purchaseOrder));

        $completeResponse->assertRedirect();
        $completeResponse->assertSessionHas('success', 'Purchase order marked as completed.');

        $purchaseOrder->refresh();
        $this->assertSame('closed', $purchaseOrder->status);
        $this->assertSame('completed', $purchaseOrder->workflow_status);
        $this->assertNotNull($purchaseOrder->purchased_at);

        $this->assertDatabaseHas('expenses', [
            'purchase_order_id' => $purchaseOrder->id,
            'amount' => 72,
        ]);
    }

    #[Test]
    public function purchaser_cannot_mark_purchase_order_completed(): void
    {
        $entity = Entity::create([
            'name' => 'Gatekeep Kitchen',
            'slug' => 'gatekeep-kitchen',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $chef = User::factory()->create(['entity_id' => $entity->id]);

        $requisition = ChefRequisition::create([
            'chef_id' => $chef->id,
            'requested_for_date' => now()->addDay(),
            'items' => [[
                'item' => 'Vanilla',
                'vendor' => 'Spice Supplier',
                'quantity' => 3,
                'uom' => 'bottle',
                'price' => 15,
            ]],
            'status' => 'approved',
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-NOT-SENT',
            'requisition_id' => $requisition->id,
            'created_by' => $chef->id,
            'items' => [[
                'item' => 'Vanilla',
                'vendor' => 'Spice Supplier',
                'quantity' => 3,
                'uom' => 'bottle',
                'price' => 15,
            ]],
            'total_quantity' => 3,
            'subtotal' => 45,
            'tax' => 0,
            'other_charges' => 0,
            'grand_total' => 45,
            'status' => 'open',
            'workflow_status' => 'approved',
        ]);

        $viewPermission = Permission::firstOrCreate(['name' => 'view purchase orders', 'guard_name' => 'web']);
        $sendPermission = Permission::firstOrCreate(['name' => 'send purchase orders', 'guard_name' => 'web']);

        $purchaserRole = Role::firstOrCreate(['name' => 'purchaser-limited', 'guard_name' => 'web']);
        $purchaserRole->syncPermissions([$sendPermission, $viewPermission]);

        $purchaser = User::factory()->create(['entity_id' => $entity->id]);
        $purchaser->assignRole($purchaserRole);

        $this->actingAs($purchaser)->post(route('purchase-orders.send', $purchaseOrder));

        $purchaseOrder->refresh();
        $this->assertSame('sent_to_vendor', $purchaseOrder->workflow_status);

        $response = $this->actingAs($purchaser)->post(route('purchase-orders.complete', $purchaseOrder));

        $response->assertForbidden();
    }

    #[Test]
    public function chef_can_view_purchase_order_details(): void
    {
        $entity = Entity::create([
            'name' => 'Read Only Ristorante',
            'slug' => 'read-only-ristorante',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $chefUser = User::factory()->create(['entity_id' => $entity->id]);

        $viewPermission = Permission::firstOrCreate(['name' => 'view purchase orders', 'guard_name' => 'web']);
        $chefRole = Role::firstOrCreate(['name' => 'chef-viewer', 'guard_name' => 'web']);
        $chefRole->syncPermissions([$viewPermission]);

        $chefUser->assignRole($chefRole);

        $requisition = ChefRequisition::create([
            'chef_id' => $chefUser->id,
            'requested_for_date' => now()->addDay(),
            'items' => [[
                'item' => 'Herbs',
                'vendor' => 'Garden Supplier',
                'quantity' => 1,
                'uom' => 'bundle',
                'price' => 12,
            ]],
            'status' => 'approved',
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-CHEF-VIEW',
            'requisition_id' => $requisition->id,
            'created_by' => $chefUser->id,
            'items' => $requisition->items,
            'total_quantity' => 1,
            'subtotal' => 12,
            'tax' => 0,
            'other_charges' => 0,
            'grand_total' => 12,
            'status' => 'open',
            'workflow_status' => 'approved',
        ]);

        $response = $this->actingAs($chefUser)->get(route('purchase-orders.show', $purchaseOrder));

        $response->assertOk();
    }

    #[Test]
    public function detailed_purchase_completion_updates_workflow_status(): void
    {
        $entity = Entity::create([
            'name' => 'Full Detail Bistro',
            'slug' => 'full-detail-bistro',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $vendor = Vendor::create([
            'name' => 'Detailed Vendor',
            'email' => 'orders@detailedvendor.test',
            'phone' => '+15550111111',
            'is_active' => true,
        ]);

        $chef = User::factory()->create(['entity_id' => $entity->id]);

        $requisition = ChefRequisition::create([
            'chef_id' => $chef->id,
            'requested_for_date' => now()->addDay(),
            'items' => [[
                'item' => 'Sugar',
                'vendor' => $vendor->name,
                'quantity' => 5,
                'uom' => 'bag',
                'price' => 12,
            ]],
            'status' => 'approved',
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-DETAIL-01',
            'requisition_id' => $requisition->id,
            'created_by' => $chef->id,
            'supplier_id' => $vendor->id,
            'items' => [[
                'item' => 'Sugar',
                'vendor' => $vendor->name,
                'quantity' => 5,
                'uom' => 'bag',
                'price' => 12,
            ]],
            'total_quantity' => 5,
            'subtotal' => 60,
            'tax' => 0,
            'other_charges' => 0,
            'grand_total' => 60,
            'status' => 'open',
            'workflow_status' => 'sent_to_vendor',
        ]);

        $markPermission = Permission::firstOrCreate(['name' => 'mark purchased', 'guard_name' => 'web']);
        $approvePermission = Permission::firstOrCreate(['name' => 'approve purchase orders', 'guard_name' => 'web']);
        $viewPermission = Permission::firstOrCreate(['name' => 'view purchase orders', 'guard_name' => 'web']);

        $role = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $role->syncPermissions([$markPermission, $approvePermission, $viewPermission]);

        $manager = User::factory()->create(['entity_id' => $entity->id]);
        $manager->assignRole($role);

        Sanctum::actingAs($manager);

        $response = $this->postJson(route('api.purchase-orders.mark-purchased', $purchaseOrder), [
            'supplier_id' => $vendor->id,
            'invoice_number' => 'INV-9001',
            'total_amount' => 60,
        ]);

        $response->assertOk();

        $purchaseOrder->refresh();

        $this->assertSame('closed', $purchaseOrder->status);
        $this->assertSame('completed', $purchaseOrder->workflow_status);
        $this->assertNotNull($purchaseOrder->purchased_at);
    }
}
