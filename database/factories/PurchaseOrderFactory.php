<?php

namespace Database\Factories;

use App\Models\ChefRequisition;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 20);
        $price = fake()->numberBetween(5000, 15000);
        $subtotal = $quantity * $price;
        $vendorName = fake()->company();

        return [
            'po_number' => sprintf('PO-%s', fake()->unique()->numerify('########')),
            'requisition_id' => ChefRequisition::factory(),
            'created_by' => User::factory(),
            'assigned_to' => User::factory(),
            'items' => [[
                'item' => fake()->randomElement(['Tomatoes', 'Cooking Oil', 'Rice']),
                'quantity' => $quantity,
                'uom' => fake()->randomElement(['kg', 'litre', 'bag']),
                'price' => $price,
                'vendor' => $vendorName,
            ]],
            'total_quantity' => $quantity,
            'subtotal' => $subtotal,
            'tax' => 0,
            'other_charges' => 0,
            'grand_total' => $subtotal,
            'status' => 'assigned',
            'supplier_id' => Vendor::factory(),
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
