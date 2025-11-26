<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->string('uom'); // Unit of Measure
            $table->string('vendor'); // Primary vendor/supplier
            $table->decimal('price', 10, 2); // Current price in TZS
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->decimal('stock', 10, 2)->nullable(); // Current stock level
            $table->decimal('reorder_level', 10, 2)->nullable(); // Low stock alert threshold
            $table->text('description')->nullable();
            $table->timestamps();
            
            // Indexes for better search performance
            $table->index('name');
            $table->index('category');
            $table->index('status');
        });
        
        // Price history table for tracking price changes
        Schema::create('item_price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->decimal('old_price', 10, 2);
            $table->decimal('new_price', 10, 2);
            $table->string('changed_by')->nullable();
            $table->timestamp('changed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_price_history');
        Schema::dropIfExists('items');
    }
};
