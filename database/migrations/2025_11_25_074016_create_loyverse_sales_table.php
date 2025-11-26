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
        Schema::create('loyverse_sales', function (Blueprint $table) {
            $table->id();
            $table->string('loyverse_receipt_number')->unique();
            $table->date('sale_date');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('store_name')->nullable();
            $table->json('line_items')->nullable();
            $table->text('raw_data')->nullable();
            $table->timestamps();
            
            $table->index('sale_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyverse_sales');
    }
};
