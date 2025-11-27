<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex('purchase_orders_status_index');
            $table->dropColumn('status');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->enum('status', ['open', 'assigned', 'ordered', 'partially_received', 'received', 'closed', 'cancelled'])->default('open')->after('grand_total');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex('purchase_orders_status_index');
            $table->dropColumn('status');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->enum('status', ['open', 'ordered', 'partially_received', 'received', 'closed', 'cancelled'])->default('open')->after('grand_total');
            $table->index('status');
        });
    }
};
