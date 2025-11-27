<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('purchase_orders', 'workflow_status')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->string('workflow_status')->default('pending')->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('purchase_orders', 'workflow_status')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->dropColumn('workflow_status');
            });
        }
    }
};
