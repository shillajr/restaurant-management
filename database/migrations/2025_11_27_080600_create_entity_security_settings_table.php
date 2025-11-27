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
        Schema::create('entity_security_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('entities')->cascadeOnDelete()->unique();
            $table->boolean('two_factor_enabled')->default(false);
            $table->boolean('session_timeout_enabled')->default(false);
            $table->integer('session_timeout_minutes')->default(30);
            $table->boolean('ip_whitelist_enabled')->default(false);
            $table->json('ip_whitelist')->nullable();
            $table->boolean('password_expiry_enabled')->default(false);
            $table->integer('password_expiry_days')->default(90);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entity_security_settings');
    }
};
