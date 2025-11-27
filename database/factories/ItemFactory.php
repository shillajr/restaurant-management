<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'category' => fake()->randomElement(['Meat', 'Vegetables', 'Dairy', 'Spices', 'Beverages']),
            'uom' => fake()->randomElement(['kg', 'pcs', 'lbs', 'liters']),
            'vendor' => fake()->company(),
            'price' => fake()->randomFloat(2, 1, 100),
            'status' => 'active',
            'stock' => fake()->randomFloat(2, 0, 1000),
            'reorder_level' => fake()->randomFloat(2, 10, 100),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
