<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntityNotificationSetting extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'entity_id',
        'notify_requisitions',
        'notify_expenses',
        'notify_purchase_orders',
        'notify_payroll',
        'notify_email_daily',
        'sms_enabled',
        'whatsapp_enabled',
        'sms_provider',
        'notification_channels',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'notify_requisitions' => 'boolean',
        'notify_expenses' => 'boolean',
        'notify_purchase_orders' => 'boolean',
        'notify_payroll' => 'boolean',
        'notify_email_daily' => 'boolean',
        'sms_enabled' => 'boolean',
        'whatsapp_enabled' => 'boolean',
        'notification_channels' => 'array',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }
}
