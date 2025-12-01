<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class VendorManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::findOrCreate('manage settings');
    }

    public function test_vendor_centralization_migration_is_reversible(): void
    {
        Storage::fake('local');

        Vendor::query()->where('name', 'Standard Vendor')->delete();

        $legacyVendor = Vendor::create([
            'name' => 'Legacy Supplies Co',
            'email' => 'legacy@example.com',
            'phone' => '+255712345678',
            'is_active' => true,
        ]);

        $purchaseOrder = PurchaseOrder::factory()->create([
            'supplier_id' => $legacyVendor->id,
        ]);

        $expense = Expense::factory()->create([
            'vendor_id' => $legacyVendor->id,
            'vendor' => $legacyVendor->name,
            'item_name' => 'Cooking Oil',
            'quantity' => 2,
            'unit_price' => 10000,
            'amount' => 20000,
        ]);

        $migration = require database_path('migrations/2025_11_30_160000_centralize_vendor_records.php');
        $migration->up();

        $canonicalVendor = Vendor::where('name', 'Standard Vendor')->first();
        $this->assertNotNull($canonicalVendor, 'Canonical vendor should be created.');
        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'supplier_id' => $canonicalVendor->id,
        ]);
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'vendor_id' => $canonicalVendor->id,
        ]);
        $this->assertDatabaseMissing('vendors', ['id' => $legacyVendor->id]);

        $migration->down();

        $this->assertDatabaseHas('vendors', [
            'id' => $legacyVendor->id,
            'name' => 'Legacy Supplies Co',
        ]);
        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'supplier_id' => $legacyVendor->id,
        ]);
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'vendor_id' => $legacyVendor->id,
        ]);
        $this->assertDatabaseMissing('vendors', ['name' => 'Standard Vendor']);
    }

    public function test_settings_vendors_tab_requires_permission(): void
    {
        $userWithoutPermission = User::factory()->create();
        $this->actingAs($userWithoutPermission)
            ->get(route('settings', ['tab' => 'products', 'product_section' => 'vendors']))
            ->assertForbidden();

        $authorizedUser = User::factory()->create();
        $authorizedUser->givePermissionTo('manage settings');

        $vendor = Vendor::firstOrCreate(
            ['name' => 'Standard Vendor'],
            [
                'is_active' => true,
                'phone' => '+255757022929',
            ]
        );

        $this->actingAs($authorizedUser)
            ->get(route('settings', ['tab' => 'products', 'product_section' => 'vendors']))
            ->assertOk()
            ->assertSee('Vendor Directory')
            ->assertSee($vendor->name);
    }

    public function test_items_store_requires_existing_vendor_id(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage settings');

        $payload = [
            'name' => 'Test Item',
            'category' => 'Produce',
            'uom' => 'kg',
            'vendor_id' => 999999,
            'price' => 1500,
            'status' => 'active',
        ];

        $response = $this->actingAs($user)
            ->post(route('items.store'), $payload);

        $response->assertSessionHasErrors(['vendor_id'], null, 'items');
        $this->assertDatabaseMissing('items', ['name' => 'Test Item']);
    }

    public function test_vendor_archive_and_restore_routes(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage settings');

        $vendor = Vendor::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->patch(route('vendors.archive', $vendor))
            ->assertRedirect(route('settings', ['tab' => 'products', 'product_section' => 'vendors']))
            ->assertSessionHas('success');

        $this->assertFalse((bool) $vendor->fresh()->is_active);

        $this->actingAs($user)
            ->patch(route('vendors.restore', $vendor))
            ->assertRedirect(route('settings', ['tab' => 'products', 'product_section' => 'vendors']))
            ->assertSessionHas('success');

        $this->assertTrue((bool) $vendor->fresh()->is_active);
    }
}
