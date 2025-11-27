<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Entity extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'contact_email',
        'contact_phone',
        'timezone',
        'currency',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function generalSettings(): HasOne
    {
        return $this->hasOne(EntityGeneralSetting::class);
    }

    public function profileSettings(): HasOne
    {
        return $this->hasOne(EntityProfileSetting::class);
    }

    public function notificationSettings(): HasOne
    {
        return $this->hasOne(EntityNotificationSetting::class);
    }

    public function integrationSettings(): HasOne
    {
        return $this->hasOne(EntityIntegrationSetting::class);
    }

    public function securitySettings(): HasOne
    {
        return $this->hasOne(EntitySecuritySetting::class);
    }
}
