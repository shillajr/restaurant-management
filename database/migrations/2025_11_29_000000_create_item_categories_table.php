<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // Backfill category names from existing items for continuity
        if (Schema::hasTable('items')) {
            $categories = DB::table('items')
                ->select('category')
                ->distinct()
                ->pluck('category')
                ->filter();

            foreach ($categories as $category) {
                DB::table('item_categories')->updateOrInsert(
                    ['name' => $category],
                    ['status' => 'active', 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('item_categories');
    }
};
