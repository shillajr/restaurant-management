<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ChefRequisition;
use App\Models\PurchaseOrder;
use App\Models\Expense;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Activitylog\Models\Activity;

class ChefRequisitionWorkflowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $chef;
    protected $manager;
    protected $purchaser;
    protected $finance;
    protected $unauthorized;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        $chefRole = Role::create(['name' => 'chef']);
        $managerRole = Role::create(['name' => 'manager']);
        $purchaserRole = Role::create(['name' => 'purchaser']);
        $financeRole = Role::create(['name' => 'finance']);

        // Create permissions
        Permission::create(['name' => 'create requisitions']);
        Permission::create(['name' => 'view requisitions']);
        Permission::create(['name' => 'approve requisitions']);
        Permission::create(['name' => 'reject requisitions']);
        Permission::create(['name' => 'create purchase orders']);
        Permission::create(['name' => 'view purchase orders']);
        Permission::create(['name' => 'mark purchase orders as purchased']);
        Permission::create(['name' => 'create expenses']);

        // Assign permissions to roles
        $chefRole->givePermissionTo(['create requisitions', 'view requisitions']);
        $managerRole->givePermissionTo(['view requisitions', 'approve requisitions', 'reject requisitions']);
        $purchaserRole->givePermissionTo(['view requisitions', 'create purchase orders', 'view purchase orders', 'mark purchase orders as purchased']);
        $financeRole->givePermissionTo(['create expenses']);

        // Create test users
        $this->chef = User::factory()->create(['name' => 'Test Chef']);
        $this->chef->assignRole('chef');

        $this->manager = User::factory()->create(['name' => 'Test Manager']);
        $this->manager->assignRole('manager');

        $this->purchaser = User::factory()->create(['name' => 'Test Purchaser']);
        $this->purchaser->assignRole('purchaser');

        $this->finance = User::factory()->create(['name' => 'Test Finance']);
        $this->finance->assignRole('finance');

        $this->unauthorized = User::factory()->create(['name' => 'Unauthorized User']);
    }

    /** @test */
    public function test_chef_can_create_requisition()
    {
        $this->actingAs($this->chef, 'sanctum');

        $requisitionData = [
            'requested_for_date' => now()->addDay()->format('Y-m-d'),
            'items' => [
                ['item' => 'Tomatoes', 'quantity' => 5, 'unit' => 'kg'],
                ['item' => 'Chicken', 'quantity' => 3, 'unit' => 'kg']
            ],
            'note' => 'Test requisition for weekend menu'
        ];

        $response = $this->postJson('/api/requisitions', $requisitionData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'id',
                     'chef_id',
                     'status',
                     'requested_for_date',
                     'items',
                     'created_at'
                 ]);

        $this->assertDatabaseHas('chef_requisitions', [
            'chef_id' => $this->chef->id,
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function test_chef_can_view_own_requisitions()
    {
        $ownRequisition = ChefRequisition::factory()->create([
            'chef_id' => $this->chef->id
        ]);

        $otherRequisition = ChefRequisition::factory()->create([
            'chef_id' => $this->manager->id
        ]);

        $this->actingAs($this->chef, 'sanctum');

        $response = $this->getJson('/api/requisitions');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonFragment(['id' => $ownRequisition->id])
                 ->assertJsonMissing(['id' => $otherRequisition->id]);
    }

    /** @test */
    public function test_manager_can_view_all_requisitions()
    {
        ChefRequisition::factory()->count(3)->create();

        $this->actingAs($this->manager, 'sanctum');

        $response = $this->getJson('/api/requisitions');

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function test_manager_can_approve_pending_requisition()
    {
        $requisition = ChefRequisition::factory()->pending()->create([
            'chef_id' => $this->chef->id
        ]);

        $this->actingAs($this->manager, 'sanctum');

        $response = $this->postJson("/api/requisitions/{$requisition->id}/approve", [
            'approval_notes' => 'Approved for purchase'
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'status' => 'approved',
                     'checker_id' => $this->manager->id
                 ]);

        $this->assertDatabaseHas('chef_requisitions', [
            'id' => $requisition->id,
            'status' => 'approved',
            'checker_id' => $this->manager->id
        ]);

        $this->assertNotNull($requisition->fresh()->checked_at);
    }

    /** @test */
    public function test_manager_can_reject_pending_requisition()
    {
        $requisition = ChefRequisition::factory()->pending()->create([
            'chef_id' => $this->chef->id
        ]);

        $this->actingAs($this->manager, 'sanctum');

        $response = $this->postJson("/api/requisitions/{$requisition->id}/reject", [
            'rejection_reason' => 'Items not in budget'
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'status' => 'rejected',
                     'checker_id' => $this->manager->id
                 ]);

        $this->assertDatabaseHas('chef_requisitions', [
            'id' => $requisition->id,
            'status' => 'rejected',
            'checker_id' => $this->manager->id
        ]);
    }

    /** @test */
    public function test_cannot_approve_already_approved_requisition()
    {
        $requisition = ChefRequisition::factory()->approved()->create();

        $this->actingAs($this->manager, 'sanctum');

        $response = $this->postJson("/api/requisitions/{$requisition->id}/approve");

        $response->assertStatus(422);
    }

    /** @test */
    public function test_cannot_approve_rejected_requisition()
    {
        $requisition = ChefRequisition::factory()->rejected()->create();

        $this->actingAs($this->manager, 'sanctum');

        $response = $this->postJson("/api/requisitions/{$requisition->id}/approve");

        $response->assertStatus(422);
    }

    /** @test */
    public function test_chef_cannot_approve_own_requisition()
    {
        $requisition = ChefRequisition::factory()->pending()->create([
            'chef_id' => $this->chef->id
        ]);

        $this->actingAs($this->chef, 'sanctum');

        $response = $this->postJson("/api/requisitions/{$requisition->id}/approve");

        $response->assertStatus(403);
    }

    /** @test */
    public function test_unauthorized_user_cannot_approve_requisition()
    {
        $requisition = ChefRequisition::factory()->pending()->create();

        $this->actingAs($this->unauthorized, 'sanctum');

        $response = $this->postJson("/api/requisitions/{$requisition->id}/approve");

        $response->assertStatus(403);
    }

    /** @test */
    public function test_purchaser_can_create_purchase_order_from_approved_requisition()
    {
        $requisition = ChefRequisition::factory()->approved()->create([
            'chef_id' => $this->chef->id,
            'checker_id' => $this->manager->id
        ]);

        $this->actingAs($this->purchaser, 'sanctum');

        $response = $this->postJson('/api/purchase-orders', [
            'requisition_id' => $requisition->id,
            'assigned_to' => $this->purchaser->id
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'id',
                     'requisition_id',
                     'assigned_to',
                     'status',
                     'created_at'
                 ]);

        $this->assertDatabaseHas('purchase_orders', [
            'requisition_id' => $requisition->id,
            'assigned_to' => $this->purchaser->id,
            'status' => 'assigned'
        ]);
    }

    /** @test */
    public function test_cannot_create_purchase_order_from_pending_requisition()
    {
        $requisition = ChefRequisition::factory()->pending()->create();

        $this->actingAs($this->purchaser, 'sanctum');

        $response = $this->postJson('/api/purchase-orders', [
            'requisition_id' => $requisition->id,
            'assigned_to' => $this->purchaser->id
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_cannot_create_purchase_order_from_rejected_requisition()
    {
        $requisition = ChefRequisition::factory()->rejected()->create();

        $this->actingAs($this->purchaser, 'sanctum');

        $response = $this->postJson('/api/purchase-orders', [
            'requisition_id' => $requisition->id,
            'assigned_to' => $this->purchaser->id
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_purchaser_can_mark_purchase_order_as_purchased()
    {
        $purchaseOrder = PurchaseOrder::factory()->assigned()->create([
            'assigned_to' => $this->purchaser->id
        ]);

        $this->actingAs($this->purchaser, 'sanctum');

        $purchaseData = [
            'supplier_id' => 1,
            'invoice_number' => 'INV-001',
            'total_amount' => 150.75
        ];

        $response = $this->postJson("/api/purchase-orders/{$purchaseOrder->id}/mark-purchased", $purchaseData);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'status' => 'purchased',
                     'invoice_number' => 'INV-001',
                     'total_amount' => '150.75'
                 ]);

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'status' => 'purchased',
            'supplier_id' => 1,
            'invoice_number' => 'INV-001',
            'total_amount' => 150.75
        ]);

        $this->assertNotNull($purchaseOrder->fresh()->purchased_at);
    }

    /** @test */
    public function test_expense_created_when_purchase_marked_as_purchased()
    {
        $requisition = ChefRequisition::factory()->approved()->create();
        
        $purchaseOrder = PurchaseOrder::factory()->assigned()->create([
            'requisition_id' => $requisition->id,
            'assigned_to' => $this->purchaser->id
        ]);

        $this->actingAs($this->purchaser, 'sanctum');

        $purchaseData = [
            'supplier_id' => 1,
            'invoice_number' => 'INV-002',
            'total_amount' => 250.50
        ];

        $response = $this->postJson("/api/purchase-orders/{$purchaseOrder->id}/mark-purchased", $purchaseData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('expenses', [
            'purchase_order_id' => $purchaseOrder->id,
            'amount' => 250.50,
            'category' => 'Food & Beverage',
            'created_by' => $this->purchaser->id
        ]);
    }

    /** @test */
    public function test_audit_log_created_on_requisition_creation()
    {
        $this->actingAs($this->chef, 'sanctum');

        $requisitionData = [
            'requested_for_date' => now()->addDay()->format('Y-m-d'),
            'items' => [
                ['item' => 'Test Item', 'quantity' => 1, 'unit' => 'kg']
            ],
            'note' => 'Test note'
        ];

        $response = $this->postJson('/api/requisitions', $requisitionData);
        
        $requisition = ChefRequisition::latest()->first();

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => ChefRequisition::class,
            'subject_id' => $requisition->id,
            'causer_id' => $this->chef->id
        ]);
    }

    /** @test */
    public function test_audit_log_created_on_approval()
    {
        $requisition = ChefRequisition::factory()->pending()->create([
            'chef_id' => $this->chef->id
        ]);

        $this->actingAs($this->manager, 'sanctum');
        
        $this->postJson("/api/requisitions/{$requisition->id}/approve");

        $activities = Activity::where('subject_type', ChefRequisition::class)
                              ->where('subject_id', $requisition->id)
                              ->where('description', 'updated')
                              ->get();

        $this->assertTrue($activities->count() > 0);
        
        $latestActivity = $activities->last();
        $this->assertEquals($this->manager->id, $latestActivity->causer_id);
    }

    /** @test */
    public function test_audit_log_created_on_rejection()
    {
        $requisition = ChefRequisition::factory()->pending()->create();

        $this->actingAs($this->manager, 'sanctum');
        
        $this->postJson("/api/requisitions/{$requisition->id}/reject", [
            'rejection_reason' => 'Budget constraints'
        ]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => ChefRequisition::class,
            'subject_id' => $requisition->id,
            'causer_id' => $this->manager->id
        ]);
    }

    /** @test */
    public function test_requisition_validation_requires_future_date()
    {
        $this->actingAs($this->chef, 'sanctum');

        $requisitionData = [
            'requested_for_date' => now()->subDay()->format('Y-m-d'), // Past date
            'items' => [
                ['item' => 'Test Item', 'quantity' => 1, 'unit' => 'kg']
            ]
        ];

        $response = $this->postJson('/api/requisitions', $requisitionData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('requested_for_date');
    }

    /** @test */
    public function test_requisition_validation_requires_items()
    {
        $this->actingAs($this->chef, 'sanctum');

        $requisitionData = [
            'requested_for_date' => now()->addDay()->format('Y-m-d'),
            'items' => [] // Empty items
        ];

        $response = $this->postJson('/api/requisitions', $requisitionData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('items');
    }

    /** @test */
    public function test_purchase_order_validation_requires_valid_amounts()
    {
        $purchaseOrder = PurchaseOrder::factory()->assigned()->create([
            'assigned_to' => $this->purchaser->id
        ]);

        $this->actingAs($this->purchaser, 'sanctum');

        $purchaseData = [
            'supplier_id' => 1,
            'invoice_number' => 'INV-003',
            'total_amount' => -50.00 // Invalid negative amount
        ];

        $response = $this->postJson("/api/purchase-orders/{$purchaseOrder->id}/mark-purchased", $purchaseData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('total_amount');
    }

    /** @test */
    public function test_complete_workflow_from_requisition_to_expense()
    {
        // Step 1: Chef creates requisition
        $this->actingAs($this->chef, 'sanctum');
        
        $requisitionResponse = $this->postJson('/api/requisitions', [
            'requested_for_date' => now()->addDays(2)->format('Y-m-d'),
            'items' => [
                ['item' => 'Beef', 'quantity' => 10, 'unit' => 'kg'],
                ['item' => 'Potatoes', 'quantity' => 20, 'unit' => 'kg']
            ],
            'note' => 'Weekly supplies'
        ]);

        $requisitionResponse->assertStatus(201);
        $requisition = ChefRequisition::latest()->first();

        // Step 2: Manager approves requisition
        $this->actingAs($this->manager, 'sanctum');
        
        $approvalResponse = $this->postJson("/api/requisitions/{$requisition->id}/approve");
        $approvalResponse->assertStatus(200);

        // Step 3: Purchaser creates purchase order
        $this->actingAs($this->purchaser, 'sanctum');
        
        $poResponse = $this->postJson('/api/purchase-orders', [
            'requisition_id' => $requisition->id,
            'assigned_to' => $this->purchaser->id
        ]);

        $poResponse->assertStatus(201);
        $purchaseOrder = PurchaseOrder::latest()->first();

        // Step 4: Purchaser marks as purchased
        $purchaseResponse = $this->postJson("/api/purchase-orders/{$purchaseOrder->id}/mark-purchased", [
            'supplier_id' => 1,
            'invoice_number' => 'INV-WORKFLOW-001',
            'total_amount' => 350.00
        ]);

        $purchaseResponse->assertStatus(200);

        // Verify entire workflow
        $this->assertDatabaseHas('chef_requisitions', [
            'id' => $requisition->id,
            'chef_id' => $this->chef->id,
            'checker_id' => $this->manager->id,
            'status' => 'approved'
        ]);

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'requisition_id' => $requisition->id,
            'status' => 'purchased',
            'total_amount' => 350.00
        ]);

        $this->assertDatabaseHas('expenses', [
            'purchase_order_id' => $purchaseOrder->id,
            'amount' => 350.00
        ]);

        // Verify audit trail exists for each step
        $activities = Activity::whereIn('subject_type', [
            ChefRequisition::class,
            PurchaseOrder::class
        ])->get();

        $this->assertTrue($activities->count() >= 3); // Create, Approve, Purchase
    }

    /** @test */
    public function test_unauthenticated_user_cannot_access_api()
    {
        $response = $this->getJson('/api/requisitions');
        $response->assertStatus(401);
    }

    /** @test */
    public function test_user_can_only_delete_own_pending_requisitions()
    {
        $ownRequisition = ChefRequisition::factory()->pending()->create([
            'chef_id' => $this->chef->id
        ]);

        $otherRequisition = ChefRequisition::factory()->pending()->create([
            'chef_id' => $this->manager->id
        ]);

        $this->actingAs($this->chef, 'sanctum');

        // Can delete own
        $response = $this->deleteJson("/api/requisitions/{$ownRequisition->id}");
        $response->assertStatus(200);

        // Cannot delete others'
        $response = $this->deleteJson("/api/requisitions/{$otherRequisition->id}");
        $response->assertStatus(403);
    }

    /** @test */
    public function test_cannot_delete_approved_requisition()
    {
        $requisition = ChefRequisition::factory()->approved()->create([
            'chef_id' => $this->chef->id
        ]);

        $this->actingAs($this->chef, 'sanctum');

        $response = $this->deleteJson("/api/requisitions/{$requisition->id}");
        
        $response->assertStatus(422);
    }
}
