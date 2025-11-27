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
            $table->string('external_id')->unique();
            $table->date('date');
            $table->decimal('total_sales', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->json('items')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('receipt_number')->nullable();
            $table->timestamp('created_at_external')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index('date');
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
