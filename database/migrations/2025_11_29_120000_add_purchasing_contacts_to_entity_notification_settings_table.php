<?php

declare(strict_types=1);

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
        Schema::table('entity_notification_settings', function (Blueprint $table) {
            $table->json('purchase_order_notification_emails')->nullable()->after('notification_channels');
            $table->json('purchase_order_notification_phones')->nullable()->after('purchase_order_notification_emails');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entity_notification_settings', function (Blueprint $table) {
            $table->dropColumn(['purchase_order_notification_emails', 'purchase_order_notification_phones']);
        });
    }
};
