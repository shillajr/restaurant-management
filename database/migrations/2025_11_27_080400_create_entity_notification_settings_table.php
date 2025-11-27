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
        Schema::create('entity_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('entities')->cascadeOnDelete()->unique();
            $table->boolean('notify_requisitions')->default(true);
            $table->boolean('notify_expenses')->default(true);
            $table->boolean('notify_purchase_orders')->default(true);
            $table->boolean('notify_payroll')->default(false);
            $table->boolean('notify_email_daily')->default(false);
            $table->boolean('sms_enabled')->default(false);
            $table->boolean('whatsapp_enabled')->default(false);
            $table->string('sms_provider')->nullable();
            $table->json('notification_channels')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entity_notification_settings');
    }
};
