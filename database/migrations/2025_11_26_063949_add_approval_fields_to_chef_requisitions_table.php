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
        Schema::table('chef_requisitions', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('checked_at');
            $table->text('change_request')->nullable()->after('rejection_reason');
            $table->enum('status', ['pending', 'approved', 'rejected', 'fulfilled', 'changes_requested'])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chef_requisitions', function (Blueprint $table) {
            $table->dropColumn(['rejection_reason', 'change_request']);
            $table->enum('status', ['pending', 'approved', 'rejected', 'fulfilled'])->default('pending')->change();
        });
    }
};
