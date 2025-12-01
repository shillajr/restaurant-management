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
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseOrderVendorNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function vendor_receives_whatsapp_message_with_item_list_when_purchase_order_is_sent(): void
    {
        Http::fake([
            'https://api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201),
        ]);

        $entity = Entity::create([
            'name' => 'Fresh Start Kitchen',
            'slug' => 'fresh-start-kitchen',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        EntityNotificationSetting::create([
            'entity_id' => $entity->id,
            'notify_requisitions' => false,
            'notify_expenses' => false,
            'notify_purchase_orders' => true,
            'notify_payroll' => false,
            'notify_email_daily' => false,
            'sms_enabled' => false,
            'whatsapp_enabled' => true,
            'sms_provider' => 'twilio',
            'notification_channels' => ['whatsapp'],
            'purchase_order_notification_emails' => [],
            'purchase_order_notification_phones' => [],
        ]);

        EntityIntegrationSetting::create([
            'entity_id' => $entity->id,
            'twilio_account_sid' => 'AC1234567890',
            'twilio_auth_token' => 'secret-token',
            'twilio_sms_number' => '+15550001111',
            'twilio_sms_enabled' => true,
            'twilio_whatsapp_number' => '+15550002222',
            'twilio_whatsapp_enabled' => true,
        ]);

        $vendor = Vendor::create([
            'name' => 'Fresh Farms',
            'email' => null,
            'phone' => '+15551234567, +15557654321',
            'is_active' => true,
        ]);

        $chef = User::factory()->create(['entity_id' => $entity->id]);
        $generator = User::factory()->create(['entity_id' => $entity->id]);
        $sender = User::factory()->create(['entity_id' => $entity->id]);

        $requisition = ChefRequisition::create([
            'chef_id' => $chef->id,
            'requested_for_date' => now()->addDay(),
            'items' => [
                [
                    'item' => 'Tomatoes',
                    'vendor' => $vendor->name,
                    'quantity' => 10,
                    'uom' => 'kg',
                    'price' => 4.5,
                ],
                [
                    'item' => 'Onions',
                    'vendor' => $vendor->name,
                    'quantity' => 5,
                    'uom' => 'bag',
                    'price' => 3,
                ],
            ],
            'status' => 'approved',
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-WS-0001',
            'requisition_id' => $requisition->id,
            'created_by' => $chef->id,
            'approved_by' => $generator->id,
            'approved_at' => now(),
            'generated_by' => $generator->id,
            'supplier_id' => $vendor->id,
            'items' => $requisition->items,
            'total_quantity' => 15,
            'subtotal' => 60,
            'tax' => 0,
            'other_charges' => 0,
            'grand_total' => 60,
            'status' => 'open',
            'workflow_status' => 'approved',
        ]);

        $sendPermission = Permission::firstOrCreate(['name' => 'send purchase orders', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'purchaser', 'guard_name' => 'web']);
        $role->syncPermissions([$sendPermission]);
        $sender->assignRole($role);

        $response = $this->actingAs($sender)->post(route('purchase-orders.send', $purchaseOrder));

        $response->assertRedirect(route('purchase-orders.show', $purchaseOrder));
        $response->assertSessionHas('success', 'PO sent to vendors successfully.');

        $purchaseOrder->refresh();
        $this->assertSame('sent_to_vendor', $purchaseOrder->workflow_status);

        Http::assertSentCount(2);

        Http::assertSent(function ($request) {
            return str_contains($request['Body'], 'Items:')
                && str_contains($request['Body'], '1) Tomatoes | Qty: 10 kg | Unit: 4.50 | Line: 45.00')
                && str_contains($request['Body'], '2) Onions | Qty: 5 bag | Unit: 3.00 | Line: 15.00');
        });

        $destinations = collect(Http::recorded())
            ->map(fn (array $transaction) => $transaction[0]['To'])
            ->all();

        $this->assertEqualsCanonicalizing(
            ['whatsapp:+15551234567', 'whatsapp:+15557654321'],
            $destinations
        );
    }
}
