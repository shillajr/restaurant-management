<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\LoyverseSale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function finance_user_can_view_reports_today(): void
    {
        $user = $this->makeFinanceUser();

        $today = Carbon::today()->toDateString();

        LoyverseSale::create([
            'external_id' => 'sale-001',
            'date' => $today,
            'total_sales' => 150.25,
            'tax' => 10.50,
            'discount' => 0,
            'items' => [
                [
                    'item_name' => 'Burger Combo',
                    'quantity' => 2,
                    'price' => 12.50,
                    'line_total' => 25.00,
                ],
                [
                    'item_name' => 'Fresh Juice',
                    'quantity' => 3,
                    'price' => 4.00,
                    'line_total' => 12.00,
                ],
            ],
        ]);

        Expense::create([
            'created_by' => $user->id,
            'category' => 'Food & Beverage',
            'item_name' => 'Tomatoes',
            'description' => 'Test expense',
            'quantity' => 5,
            'unit_price' => 9.00,
            'amount' => 45.00,
            'expense_date' => $today,
        ]);

        $this->assertSame(1, LoyverseSale::count());
        $this->assertSame(1, Expense::count());
        $this->assertEquals(150.25, (float) LoyverseSale::sum('total_sales'));
        $this->assertEquals(150.25, (float) LoyverseSale::query()->whereBetween('date', [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()])->sum('total_sales'));
        $this->assertEquals(45.00, (float) Expense::sum('amount'));
        $this->assertEquals(45.00, (float) Expense::query()->whereBetween('expense_date', [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()])->sum('amount'));
        $this->assertTrue(Schema::hasColumn('loyverse_sales', 'date'));
        $this->assertTrue(Schema::hasColumn('loyverse_sales', 'total_sales'));
        $this->assertFalse(Schema::hasColumn('loyverse_sales', 'total_amount'));

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee('Reports Overview');
        $response->assertViewHas('summary', function (array $summary) {
            $this->assertEquals(150.25, $summary['total_sales']);
            $this->assertEquals(45.00, $summary['total_expenses']);
            $this->assertEquals(105.25, $summary['net_profit']);
            $this->assertEquals(0.0, $summary['accounts_receivable']);
            $this->assertEquals(0.0, $summary['vendor_credits']);
            $this->assertEquals(105.25, $summary['net_position']);

            return true;
        });

        $response->assertViewHas('topSellingItems', function ($items) {
            $this->assertCount(2, $items);
            $top = $items->first();
            $this->assertEquals('Burger Combo', $top['name']);
            $this->assertEquals(25.00, $top['revenue']);

            return true;
        });
    }

    #[Test]
    public function users_without_permission_cannot_access_reports(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertForbidden();
    }

    #[Test]
    public function custom_range_requires_valid_dates(): void
    {
        $user = $this->makeFinanceUser();

        $response = $this->actingAs($user)
            ->from(route('reports.index'))
            ->get(route('reports.index', ['range' => 'custom']));

        $response->assertRedirect(route('reports.index'));
        $response->assertSessionHasErrors(['start_date', 'end_date']);
    }

    #[Test]
    public function export_endpoint_is_stubbed(): void
    {
        $user = $this->makeFinanceUser();

        $response = $this->actingAs($user)->get(route('reports.export', ['range' => 'today']));

        $response->assertStatus(501);
        $response->assertJson(['message' => 'Report export is not available yet.']);
    }

    private function makeFinanceUser(): User
    {
        $user = User::factory()->create();

        $permission = Permission::firstOrCreate([
            'name' => 'view reports',
            'guard_name' => 'web',
        ]);

        $role = Role::firstOrCreate([
            'name' => 'finance',
            'guard_name' => 'web',
        ]);

        $role->givePermissionTo($permission);
        $user->assignRole($role);

        return $user;
    }
}
