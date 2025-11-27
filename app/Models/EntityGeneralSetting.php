<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntityGeneralSetting extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'entity_id',
        'timezone',
        'currency',
        'date_format',
        'language',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }
}
