<?php

namespace Database\Factories;

use App\Models\ChefRequisition;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChefRequisitionFactory extends Factory
{
    protected $model = ChefRequisition::class;

    public function definition(): array
    {
        return [
            'chef_id' => User::factory(),
            'requested_for_date' => fake()->dateTimeBetween('now', '+7 days'),
            'items' => [
                [
                    'item' => fake()->randomElement(['Tomatoes', 'Onions', 'Chicken', 'Beef', 'Rice', 'Pasta']),
                    'quantity' => fake()->numberBetween(1, 20),
                    'unit' => fake()->randomElement(['kg', 'pcs', 'lbs'])
                ],
                [
                    'item' => fake()->randomElement(['Salt', 'Pepper', 'Oil', 'Garlic', 'Ginger']),
                    'quantity' => fake()->numberBetween(1, 10),
                    'unit' => fake()->randomElement(['kg', 'pcs', 'bottles'])
                ]
            ],
            'note' => fake()->optional()->sentence(),
            'status' => 'pending',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'checker_id' => User::factory(),
            'checked_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'checker_id' => User::factory(),
            'checked_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'checker_id' => null,
            'checked_at' => null,
        ]);
    }
}
