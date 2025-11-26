<?php

namespace Database\Seeders;

use App\Models\LoyverseSale;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DashboardDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $today = Carbon::today();
        
        // Create sample sales for today
        $salesData = [
            [
                'loyverse_receipt_number' => 'REC-' . str_pad(1, 8, '0', STR_PAD_LEFT),
                'sale_date' => $today,
                'total_amount' => 45.50,
                'tax_amount' => 3.64,
                'payment_method' => 'Cash',
                'store_name' => 'Main Restaurant',
                'line_items' => [
                    ['item' => 'Burger', 'quantity' => 2, 'price' => 12.99],
                    ['item' => 'Fries', 'quantity' => 2, 'price' => 4.99],
                    ['item' => 'Soda', 'quantity' => 2, 'price' => 2.50],
                ],
            ],
            [
                'loyverse_receipt_number' => 'REC-' . str_pad(2, 8, '0', STR_PAD_LEFT),
                'sale_date' => $today,
                'total_amount' => 78.90,
                'tax_amount' => 6.31,
                'payment_method' => 'Credit Card',
                'store_name' => 'Main Restaurant',
                'line_items' => [
                    ['item' => 'Steak', 'quantity' => 1, 'price' => 34.99],
                    ['item' => 'Pasta', 'quantity' => 1, 'price' => 18.99],
                    ['item' => 'Wine', 'quantity' => 1, 'price' => 18.99],
                ],
            ],
            [
                'loyverse_receipt_number' => 'REC-' . str_pad(3, 8, '0', STR_PAD_LEFT),
                'sale_date' => $today,
                'total_amount' => 125.00,
                'tax_amount' => 10.00,
                'payment_method' => 'Credit Card',
                'store_name' => 'Main Restaurant',
                'line_items' => [
                    ['item' => 'Family Platter', 'quantity' => 1, 'price' => 89.99],
                    ['item' => 'Dessert', 'quantity' => 3, 'price' => 8.99],
                ],
            ],
            [
                'loyverse_receipt_number' => 'REC-' . str_pad(4, 8, '0', STR_PAD_LEFT),
                'sale_date' => $today,
                'total_amount' => 32.50,
                'tax_amount' => 2.60,
                'payment_method' => 'Cash',
                'store_name' => 'Main Restaurant',
                'line_items' => [
                    ['item' => 'Sandwich', 'quantity' => 2, 'price' => 8.99],
                    ['item' => 'Coffee', 'quantity' => 2, 'price' => 3.50],
                ],
            ],
            [
                'loyverse_receipt_number' => 'REC-' . str_pad(5, 8, '0', STR_PAD_LEFT),
                'sale_date' => $today,
                'total_amount' => 215.75,
                'tax_amount' => 17.26,
                'payment_method' => 'Credit Card',
                'store_name' => 'Main Restaurant',
                'line_items' => [
                    ['item' => 'Group Dinner Package', 'quantity' => 1, 'price' => 199.99],
                ],
            ],
        ];

        foreach ($salesData as $sale) {
            LoyverseSale::create($sale);
        }

        $this->command->info('Created 5 sales for today totaling $497.65');

        // Get a finance user to create expenses
        $financeUser = User::role('finance')->first();
        
        if (!$financeUser) {
            $this->command->warn('No finance user found. Skipping expense creation.');
            return;
        }

        // Create sample expenses for today
        $expensesData = [
            [
                'created_by' => $financeUser->id,
                'category' => 'Food & Beverage',
                'description' => 'Fresh vegetables from supplier',
                'amount' => 85.00,
                'expense_date' => $today,
                'note' => 'Weekly vegetable order',
            ],
            [
                'created_by' => $financeUser->id,
                'category' => 'Food & Beverage',
                'description' => 'Meat products',
                'amount' => 150.00,
                'expense_date' => $today,
                'note' => 'Premium beef and chicken',
            ],
            [
                'created_by' => $financeUser->id,
                'category' => 'Utilities',
                'description' => 'Electricity bill',
                'amount' => 45.00,
                'expense_date' => $today,
                'note' => 'Monthly utility payment',
            ],
            [
                'created_by' => $financeUser->id,
                'category' => 'Supplies',
                'description' => 'Cleaning supplies',
                'amount' => 28.50,
                'expense_date' => $today,
                'note' => 'Detergents, sanitizers, etc.',
            ],
        ];

        foreach ($expensesData as $expense) {
            Expense::create($expense);
        }

        $this->command->info('Created 4 expenses for today totaling $308.50');
        $this->command->info('Today\'s Profit: $189.15 (Margin: 38.0%)');
    }
}
