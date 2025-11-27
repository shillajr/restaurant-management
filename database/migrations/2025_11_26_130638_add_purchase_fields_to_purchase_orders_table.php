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
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->constrained('vendors')->onDelete('set null')->after('requisition_id');
            $table->string('invoice_number')->nullable()->after('supplier_id');
            $table->decimal('total_amount', 12, 2)->nullable()->after('invoice_number');
            $table->timestamp('purchased_at')->nullable()->after('total_amount');
            $table->string('receipt_path')->nullable()->after('purchased_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['supplier_id', 'invoice_number', 'total_amount', 'purchased_at', 'receipt_path']);
        });
    }
};
