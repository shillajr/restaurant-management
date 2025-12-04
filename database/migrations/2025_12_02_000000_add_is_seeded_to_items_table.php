<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->boolean('is_seeded')->default(false)->after('status');
            $table->index('is_seeded');
        });

        $seededNames = [
            'Tomatoes',
            'Onions',
            'Carrots',
            'Bell Peppers',
            'Chicken Breast',
            'Beef Sirloin',
            'Pork Chops',
            'Fresh Salmon',
            'Prawns',
            'Fresh Milk',
            'Butter',
            'Cheddar Cheese',
            'Rice (Basmati)',
            'Pasta (Spaghetti)',
            'All-Purpose Flour',
            'Olive Oil',
            'Vegetable Oil',
            'Black Pepper',
            'Salt',
            'Garlic Powder',
            'Coffee Beans',
            'Tea Bags',
            'Dish Soap',
            'Paper Towels',
            'Disinfectant Spray',
            'A4 Paper',
            'Pens (Blue)',
            'Bananas',
            'Apples',
            'Oranges',
        ];

        DB::table('items')
            ->whereIn('name', $seededNames)
            ->update(['is_seeded' => true]);
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['is_seeded']);
            $table->dropColumn('is_seeded');
        });
    }
};
