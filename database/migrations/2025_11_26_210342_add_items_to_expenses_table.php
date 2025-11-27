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
        Schema::table('expenses', function (Blueprint $table) {
            $table->json('items')->nullable()->after('amount');
            $table->string('invoice_number')->nullable()->after('expense_date');
            $table->string('vendor')->nullable()->after('category');
            $table->string('payment_method')->nullable()->after('receipt_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['items', 'invoice_number', 'vendor', 'payment_method']);
        });
    }
};
