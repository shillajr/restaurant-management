<?php

namespace Database\Seeders;

use App\Models\ChefRequisition;
use App\Models\User;
use App\Models\Item;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ChefRequisitionSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Get or create a chef user
        $chef = User::firstOrCreate(
            ['email' => 'chef@example.com'],
            [
                'name' => 'Head Chef',
                'password' => bcrypt('password'),
            ]
        );

        // Get checker user (optional)
        $checker = User::firstOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Restaurant Manager',
                'password' => bcrypt('password'),
            ]
        );

        // Sample items data matching the Item Master
        $availableItems = [
            ['name' => 'Tomatoes', 'category' => 'Vegetables', 'vendor' => 'Fresh Farms Ltd', 'price' => 1500, 'uom' => 'Kg'],
            ['name' => 'Onions', 'category' => 'Vegetables', 'vendor' => 'Fresh Farms Ltd', 'price' => 1200, 'uom' => 'Kg'],
            ['name' => 'Carrots', 'category' => 'Vegetables', 'vendor' => 'Fresh Farms Ltd', 'price' => 1800, 'uom' => 'Kg'],
            ['name' => 'Potatoes', 'category' => 'Vegetables', 'vendor' => 'Green Valley Suppliers', 'price' => 1000, 'uom' => 'Kg'],
            ['name' => 'Chicken Breast', 'category' => 'Meat', 'vendor' => 'Premium Meats Co.', 'price' => 12000, 'uom' => 'Kg'],
            ['name' => 'Beef Steak', 'category' => 'Meat', 'vendor' => 'Premium Meats Co.', 'price' => 18000, 'uom' => 'Kg'],
            ['name' => 'Prawns', 'category' => 'Seafood', 'vendor' => 'Ocean Fresh Traders', 'price' => 25000, 'uom' => 'Kg'],
            ['name' => 'Fresh Milk', 'category' => 'Dairy', 'vendor' => 'Dairy Best', 'price' => 2500, 'uom' => 'Litre'],
            ['name' => 'Cheese', 'category' => 'Dairy', 'vendor' => 'Dairy Best', 'price' => 15000, 'uom' => 'Kg'],
            ['name' => 'Rice', 'category' => 'Grains', 'vendor' => 'Global Grains', 'price' => 3000, 'uom' => 'Kg'],
            ['name' => 'Olive Oil', 'category' => 'Cooking Oils', 'vendor' => 'Quality Oils Ltd', 'price' => 18000, 'uom' => 'Litre'],
            ['name' => 'Black Pepper', 'category' => 'Spices', 'vendor' => 'Spice World', 'price' => 8000, 'uom' => 'Kg'],
            ['name' => 'Salt', 'category' => 'Spices', 'vendor' => 'Spice World', 'price' => 800, 'uom' => 'Kg'],
            ['name' => 'Garlic', 'category' => 'Vegetables', 'vendor' => 'Fresh Farms Ltd', 'price' => 4000, 'uom' => 'Kg'],
            ['name' => 'Ginger', 'category' => 'Vegetables', 'vendor' => 'Fresh Farms Ltd', 'price' => 3500, 'uom' => 'Kg'],
        ];

        // Create 15 sample requisitions with varied statuses and dates
        $statuses = ['pending', 'approved', 'rejected', 'fulfilled'];
        
        for ($i = 1; $i <= 15; $i++) {
            // Generate random date within last 2 months
            $createdAt = Carbon::now()->subDays(rand(1, 60));
            $requestedForDate = Carbon::now()->addDays(rand(1, 14));
            
            // Randomly select 3-8 items
            $itemCount = rand(3, 8);
            $selectedItems = [];
            $shuffled = $availableItems;
            shuffle($shuffled);
            
            for ($j = 0; $j < $itemCount; $j++) {
                $item = $shuffled[$j];
                $quantity = rand(1, 20);
                
                // Sometimes modify the price (10% chance)
                $price = $item['price'];
                if (rand(1, 10) == 1) {
                    $price = $price * (rand(90, 110) / 100); // ±10% variation
                }
                
                $selectedItems[] = [
                    'item' => $item['name'],
                    'quantity' => $quantity,
                    'unit' => $item['uom'],
                    'vendor' => $item['vendor'],
                    'price' => $price,
                    'originalPrice' => $item['price'],
                ];
            }
            
            // Determine status based on creation date
            $status = 'pending';
            $checkerId = null;
            $checkedAt = null;
            
            if ($createdAt->diffInDays(Carbon::now()) > 7) {
                // Older requisitions have been processed
                $status = $statuses[array_rand(['approved', 'rejected', 'fulfilled'])];
                $checkerId = $checker->id;
                $checkedAt = $createdAt->copy()->addDays(rand(1, 3));
            } elseif ($createdAt->diffInDays(Carbon::now()) > 3 && rand(1, 2) == 1) {
                // Some medium-age requisitions are approved
                $status = 'approved';
                $checkerId = $checker->id;
                $checkedAt = $createdAt->copy()->addDays(rand(1, 2));
            }
            
            ChefRequisition::create([
                'chef_id' => $chef->id,
                'requested_for_date' => $requestedForDate,
                'items' => $selectedItems,
                'note' => rand(1, 3) == 1 ? "Requisition for upcoming event #$i" : null,
                'status' => $status,
                'checker_id' => $checkerId,
                'checked_at' => $checkedAt,
                'created_at' => $createdAt,
                'updated_at' => $checkedAt ?? $createdAt,
            ]);
        }

        $this->command->info('Created 15 sample chef requisitions');
    }
}
