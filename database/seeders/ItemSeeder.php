<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            // Vegetables
            ['name' => 'Tomatoes', 'category' => 'Vegetables', 'uom' => 'kg', 'vendor' => 'Fresh Farm Suppliers', 'price' => 3500, 'status' => 'active', 'stock' => 45, 'reorder_level' => 20],
            ['name' => 'Onions', 'category' => 'Vegetables', 'uom' => 'kg', 'vendor' => 'Fresh Farm Suppliers', 'price' => 2800, 'status' => 'active', 'stock' => 60, 'reorder_level' => 25],
            ['name' => 'Carrots', 'category' => 'Vegetables', 'uom' => 'kg', 'vendor' => 'Fresh Farm Suppliers', 'price' => 3200, 'status' => 'active', 'stock' => 30, 'reorder_level' => 15],
            ['name' => 'Bell Peppers', 'category' => 'Vegetables', 'uom' => 'kg', 'vendor' => 'Fresh Farm Suppliers', 'price' => 5500, 'status' => 'active', 'stock' => 18, 'reorder_level' => 10],
            
            // Meat
            ['name' => 'Chicken Breast', 'category' => 'Meat', 'uom' => 'kg', 'vendor' => 'Quality Meats Ltd', 'price' => 12000, 'status' => 'active', 'stock' => 30, 'reorder_level' => 15],
            ['name' => 'Beef Sirloin', 'category' => 'Meat', 'uom' => 'kg', 'vendor' => 'Quality Meats Ltd', 'price' => 18000, 'status' => 'active', 'stock' => 25, 'reorder_level' => 12],
            ['name' => 'Pork Chops', 'category' => 'Meat', 'uom' => 'kg', 'vendor' => 'Quality Meats Ltd', 'price' => 14000, 'status' => 'active', 'stock' => 20, 'reorder_level' => 10],
            
            // Seafood
            ['name' => 'Fresh Salmon', 'category' => 'Seafood', 'uom' => 'kg', 'vendor' => 'Ocean Fresh Suppliers', 'price' => 25000, 'status' => 'active', 'stock' => 10, 'reorder_level' => 5],
            ['name' => 'Prawns', 'category' => 'Seafood', 'uom' => 'kg', 'vendor' => 'Ocean Fresh Suppliers', 'price' => 28000, 'status' => 'active', 'stock' => 8, 'reorder_level' => 5],
            
            // Dairy
            ['name' => 'Fresh Milk', 'category' => 'Dairy', 'uom' => 'L', 'vendor' => 'Dairy Delights Co', 'price' => 3000, 'status' => 'active', 'stock' => 50, 'reorder_level' => 20],
            ['name' => 'Butter', 'category' => 'Dairy', 'uom' => 'kg', 'vendor' => 'Dairy Delights Co', 'price' => 8000, 'status' => 'active', 'stock' => 12, 'reorder_level' => 6],
            ['name' => 'Cheddar Cheese', 'category' => 'Dairy', 'uom' => 'kg', 'vendor' => 'Dairy Delights Co', 'price' => 15000, 'status' => 'active', 'stock' => 8, 'reorder_level' => 4],
            
            // Grains
            ['name' => 'Rice (Basmati)', 'category' => 'Grains', 'uom' => 'kg', 'vendor' => 'Grain Wholesalers', 'price' => 4200, 'status' => 'active', 'stock' => 100, 'reorder_level' => 50],
            ['name' => 'Pasta (Spaghetti)', 'category' => 'Grains', 'uom' => 'kg', 'vendor' => 'Grain Wholesalers', 'price' => 3500, 'status' => 'active', 'stock' => 80, 'reorder_level' => 40],
            ['name' => 'All-Purpose Flour', 'category' => 'Grains', 'uom' => 'kg', 'vendor' => 'Grain Wholesalers', 'price' => 2500, 'status' => 'active', 'stock' => 120, 'reorder_level' => 60],
            
            // Cooking Oils
            ['name' => 'Olive Oil', 'category' => 'Cooking Oils', 'uom' => 'L', 'vendor' => 'Premium Foods Co', 'price' => 8500, 'status' => 'active', 'stock' => 12, 'reorder_level' => 10],
            ['name' => 'Vegetable Oil', 'category' => 'Cooking Oils', 'uom' => 'L', 'vendor' => 'Premium Foods Co', 'price' => 5000, 'status' => 'active', 'stock' => 20, 'reorder_level' => 12],
            
            // Spices
            ['name' => 'Black Pepper', 'category' => 'Spices', 'uom' => 'g', 'vendor' => 'Spice Market Ltd', 'price' => 15000, 'status' => 'active', 'stock' => 500, 'reorder_level' => 200],
            ['name' => 'Salt', 'category' => 'Spices', 'uom' => 'kg', 'vendor' => 'Spice Market Ltd', 'price' => 1500, 'status' => 'active', 'stock' => 50, 'reorder_level' => 20],
            ['name' => 'Garlic Powder', 'category' => 'Spices', 'uom' => 'g', 'vendor' => 'Spice Market Ltd', 'price' => 12000, 'status' => 'active', 'stock' => 300, 'reorder_level' => 150],
            
            // Beverages
            ['name' => 'Coffee Beans', 'category' => 'Beverages', 'uom' => 'kg', 'vendor' => 'Coffee Masters', 'price' => 18000, 'status' => 'active', 'stock' => 15, 'reorder_level' => 8],
            ['name' => 'Tea Bags', 'category' => 'Beverages', 'uom' => 'box', 'vendor' => 'Tea Traders', 'price' => 8000, 'status' => 'active', 'stock' => 10, 'reorder_level' => 5],
            
            // Cleaning Supplies
            ['name' => 'Dish Soap', 'category' => 'Cleaning Supplies', 'uom' => 'L', 'vendor' => 'Cleaning Pro Supply', 'price' => 6000, 'status' => 'active', 'stock' => 15, 'reorder_level' => 8],
            ['name' => 'Paper Towels', 'category' => 'Cleaning Supplies', 'uom' => 'box', 'vendor' => 'Office Essentials', 'price' => 15000, 'status' => 'active', 'stock' => 8, 'reorder_level' => 5],
            ['name' => 'Disinfectant Spray', 'category' => 'Cleaning Supplies', 'uom' => 'L', 'vendor' => 'Cleaning Pro Supply', 'price' => 8500, 'status' => 'active', 'stock' => 10, 'reorder_level' => 5],
            
            // Office Supplies
            ['name' => 'A4 Paper', 'category' => 'Office Supplies', 'uom' => 'pack', 'vendor' => 'Office Essentials', 'price' => 12000, 'status' => 'active', 'stock' => 6, 'reorder_level' => 3],
            ['name' => 'Pens (Blue)', 'category' => 'Office Supplies', 'uom' => 'box', 'vendor' => 'Office Essentials', 'price' => 5000, 'status' => 'active', 'stock' => 4, 'reorder_level' => 2],
            
            // Fruits
            ['name' => 'Bananas', 'category' => 'Fruits', 'uom' => 'kg', 'vendor' => 'Fresh Farm Suppliers', 'price' => 2500, 'status' => 'active', 'stock' => 40, 'reorder_level' => 20],
            ['name' => 'Apples', 'category' => 'Fruits', 'uom' => 'kg', 'vendor' => 'Fresh Farm Suppliers', 'price' => 4500, 'status' => 'active', 'stock' => 25, 'reorder_level' => 15],
            ['name' => 'Oranges', 'category' => 'Fruits', 'uom' => 'kg', 'vendor' => 'Fresh Farm Suppliers', 'price' => 4000, 'status' => 'active', 'stock' => 30, 'reorder_level' => 15],
        ];

        foreach ($items as $item) {
            Item::create($item);
        }
    }
}
