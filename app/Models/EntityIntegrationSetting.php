<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntityIntegrationSetting extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'entity_id',
        'loyverse_api_key',
        'loyverse_auto_sync',
        'twilio_account_sid',
        'twilio_auth_token',
        'twilio_sms_number',
        'twilio_whatsapp_number',
        'twilio_whatsapp_enabled',
        'twilio_sms_enabled',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'loyverse_auto_sync' => 'boolean',
        'twilio_whatsapp_enabled' => 'boolean',
        'twilio_sms_enabled' => 'boolean',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }
}
