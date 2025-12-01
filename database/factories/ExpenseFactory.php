<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'purchase_order_id' => null,
            'created_by' => User::factory(),
            'vendor_id' => Vendor::factory(),
            'category' => fake()->randomElement(['Food & Beverage', 'Utilities', 'Supplies', 'Equipment', 'Labor']),
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'expense_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'receipt_path' => null,
            'note' => fake()->optional()->sentence(),
            'vendor' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Expense $expense) {
            if ($expense->vendor_id && ! $expense->vendor) {
                $expense->vendor = Vendor::find($expense->vendor_id)?->name;
            }
        })->afterCreating(function (Expense $expense) {
            if ($expense->vendor_id && ! $expense->vendor) {
                $expense->updateQuietly([
                    'vendor' => Vendor::find($expense->vendor_id)?->name,
                ]);
            }
        });
    }
}
