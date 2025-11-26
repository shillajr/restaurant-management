<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\ChefRequisition;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return [
            'requisition_id' => ChefRequisition::factory(),
            'assigned_to' => User::factory(),
            'status' => 'assigned',
            'supplier_id' => null,
            'invoice_number' => null,
            'total_amount' => null,
            'purchased_at' => null,
            'receipt_path' => null,
        ];
    }

    public function purchased(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'purchased',
            'supplier_id' => fake()->numberBetween(1, 10),
            'invoice_number' => 'INV-' . fake()->unique()->numberBetween(1000, 9999),
            'total_amount' => fake()->randomFloat(2, 50, 500),
            'purchased_at' => now(),
        ]);
    }

    public function assigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'assigned',
        ]);
    }
}
