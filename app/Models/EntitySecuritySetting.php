<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntitySecuritySetting extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'entity_id',
        'two_factor_enabled',
        'session_timeout_enabled',
        'session_timeout_minutes',
        'ip_whitelist_enabled',
        'ip_whitelist',
        'password_expiry_enabled',
        'password_expiry_days',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'two_factor_enabled' => 'boolean',
        'session_timeout_enabled' => 'boolean',
        'ip_whitelist_enabled' => 'boolean',
        'ip_whitelist' => 'array',
        'password_expiry_enabled' => 'boolean',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }
}
