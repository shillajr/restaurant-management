<?php

namespace Tests\Feature;

use App\Models\ChefRequisition;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChefRequisitionResubmissionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_change_requested_requisition_can_be_edited_by_its_creator(): void
    {
        $chef = User::factory()->create();
        $item = Item::factory()->create();
        Role::firstOrCreate(['name' => 'chef', 'guard_name' => 'web']);
        $chef->assignRole('chef');

        $requisition = ChefRequisition::create([
            'chef_id' => $chef->id,
            'requested_for_date' => Carbon::now()->addDays(3),
            'items' => [[
                'item_id' => $item->id,
                'item' => $item->name,
                'vendor' => $item->vendor,
                'quantity' => 5,
                'unit' => $item->uom,
                'uom' => $item->uom,
                'price' => $item->price,
                'defaultPrice' => $item->price,
                'priceEdited' => false,
                'originalPrice' => $item->price,
            ]],
            'note' => 'Initial note',
            'status' => 'changes_requested',
            'checker_id' => User::factory()->create()->id,
            'checked_at' => Carbon::now(),
            'change_request' => 'Please adjust the quantities.',
        ]);

        $response = $this->actingAs($chef)->get(route('chef-requisitions.edit', $requisition));

        $response->assertOk();
        $response->assertSeeText('Resubmit for Approval');
        $response->assertSeeText('Changes Requested');
    }

    #[Test]
    public function resubmitting_a_requisition_resets_approval_fields_and_sets_pending_status(): void
    {
        $chef = User::factory()->create();
        $checker = User::factory()->create();
        $item = Item::factory()->create(['price' => 100]);
        Role::firstOrCreate(['name' => 'chef', 'guard_name' => 'web']);
        $chef->assignRole('chef');

        $requisition = ChefRequisition::create([
            'chef_id' => $chef->id,
            'requested_for_date' => Carbon::now()->addDays(2),
            'items' => [[
                'item_id' => $item->id,
                'item' => $item->name,
                'vendor' => $item->vendor,
                'quantity' => 4,
                'unit' => $item->uom,
                'uom' => $item->uom,
                'price' => $item->price,
                'defaultPrice' => $item->price,
                'priceEdited' => false,
                'originalPrice' => $item->price,
            ]],
            'note' => 'Need soon',
            'status' => 'changes_requested',
            'checker_id' => $checker->id,
            'checked_at' => Carbon::now()->subDay(),
            'change_request' => 'Reduce quantity to 2 and confirm pricing.',
        ]);

        $payload = [
            'requested_for_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
            'items' => [[
                'item_id' => $item->id,
                'vendor' => $item->vendor,
                'quantity' => 2,
                'uom' => $item->uom,
                'price' => 90,
                'default_price' => $item->price,
                'price_edited' => '1',
                'originalPrice' => $item->price,
            ]],
            'note' => 'Updated per feedback',
        ];

        $response = $this->actingAs($chef)->put(route('chef-requisitions.update', $requisition), $payload);

        $response->assertRedirect(route('chef-requisitions.show', $requisition));

        $requisition->refresh();

        $this->assertEquals('pending', $requisition->status);
        $this->assertNull($requisition->checker_id);
        $this->assertNull($requisition->checked_at);
        $this->assertNull($requisition->change_request);
        $this->assertEquals('Updated per feedback', $requisition->note);
        $this->assertEquals(2.0, $requisition->items[0]['quantity']);
        $this->assertEquals(90.0, $requisition->items[0]['price']);
        $this->assertTrue($requisition->items[0]['priceEdited']);
    }

    #[Test]
    public function non_owner_cannot_update_requisition_even_if_changes_requested(): void
    {
        $chef = User::factory()->create();
        $otherUser = User::factory()->create();
        $item = Item::factory()->create();
        Role::firstOrCreate(['name' => 'chef', 'guard_name' => 'web']);
        $chef->assignRole('chef');
        $otherUser->assignRole('chef');

        $requisition = ChefRequisition::create([
            'chef_id' => $chef->id,
            'requested_for_date' => Carbon::now()->addDays(3),
            'items' => [[
                'item_id' => $item->id,
                'item' => $item->name,
                'vendor' => $item->vendor,
                'quantity' => 3,
                'unit' => $item->uom,
                'uom' => $item->uom,
                'price' => $item->price,
                'defaultPrice' => $item->price,
                'priceEdited' => false,
                'originalPrice' => $item->price,
            ]],
            'status' => 'changes_requested',
            'change_request' => 'Only the creator can update this.',
        ]);

        $response = $this->actingAs($otherUser)->put(route('chef-requisitions.update', $requisition), [
            'requested_for_date' => Carbon::now()->addDays(4)->format('Y-m-d'),
            'items' => [[
                'item_id' => $item->id,
                'vendor' => $item->vendor,
                'quantity' => 4,
                'uom' => $item->uom,
                'price' => $item->price,
                'default_price' => $item->price,
                'price_edited' => '0',
                'originalPrice' => $item->price,
            ]],
        ]);

        $response->assertRedirect(route('chef-requisitions.index'));
        $response->assertSessionHas('error');

        $requisition->refresh();
        $this->assertEquals('changes_requested', $requisition->status);
        $this->assertEquals(3.0, $requisition->items[0]['quantity']);
    }

    #[Test]
    public function manager_can_approve_a_change_requested_requisition(): void
    {
        $manager = User::factory()->create();
        $chef = User::factory()->create();
        $item = Item::factory()->create();

        Permission::firstOrCreate(['name' => 'approve requisitions', 'guard_name' => 'web']);
        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $managerRole->givePermissionTo('approve requisitions');
        $manager->assignRole('manager');

        $requisition = ChefRequisition::create([
            'chef_id' => $chef->id,
            'requested_for_date' => Carbon::now()->addDays(2),
            'items' => [[
                'item_id' => $item->id,
                'item' => $item->name,
                'vendor' => $item->vendor,
                'quantity' => 4,
                'unit' => $item->uom,
                'uom' => $item->uom,
                'price' => $item->price,
                'defaultPrice' => $item->price,
                'priceEdited' => false,
                'originalPrice' => $item->price,
            ]],
            'status' => 'changes_requested',
            'change_request' => 'Please confirm updated vendor pricing.',
        ]);

        $response = $this->actingAs($manager)->post(route('chef-requisitions.approve', $requisition));

        $response->assertRedirect(route('chef-requisitions.show', $requisition));

        $requisition->refresh();

        $this->assertEquals('approved', $requisition->status);
        $this->assertNull($requisition->change_request);
        $this->assertEquals($manager->id, $requisition->checker_id);
        $this->assertNotNull($requisition->checked_at);
    }

    #[Test]
    public function manager_can_reject_a_change_requested_requisition(): void
    {
        $manager = User::factory()->create();
        $chef = User::factory()->create();
        $item = Item::factory()->create();

        Permission::firstOrCreate(['name' => 'approve requisitions', 'guard_name' => 'web']);
        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $managerRole->givePermissionTo('approve requisitions');
        $manager->assignRole('manager');

        $requisition = ChefRequisition::create([
            'chef_id' => $chef->id,
            'requested_for_date' => Carbon::now()->addDays(2),
            'items' => [[
                'item_id' => $item->id,
                'item' => $item->name,
                'vendor' => $item->vendor,
                'quantity' => 4,
                'unit' => $item->uom,
                'uom' => $item->uom,
                'price' => $item->price,
                'defaultPrice' => $item->price,
                'priceEdited' => false,
                'originalPrice' => $item->price,
            ]],
            'status' => 'changes_requested',
            'change_request' => 'Please verify quantities before approval.',
        ]);

        $response = $this->actingAs($manager)->post(route('chef-requisitions.reject', $requisition), [
            'rejection_reason' => 'Details still inconsistent after review.',
        ]);

        $response->assertRedirect();
        $requisition->refresh();

        $this->assertEquals('rejected', $requisition->status);
        $this->assertNull($requisition->change_request);
        $this->assertEquals($manager->id, $requisition->checker_id);
        $this->assertEquals('Details still inconsistent after review.', $requisition->rejection_reason);
    }
}
