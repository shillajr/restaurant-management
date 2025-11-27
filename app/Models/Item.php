<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'category',
        'uom',
        'vendor',
        'price',
        'status',
        'stock',
        'reorder_level',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'decimal:2',
        'reorder_level' => 'decimal:2',
    ];

    /**
     * Get the price history for this item.
     */
    public function priceHistory(): HasMany
    {
        return $this->hasMany(ItemPriceHistory::class);
    }

    /**
     * Persist a price change in the history ledger.
     */
    public function logPriceChange($from, $to, string $changedBy): void
    {
        $this->priceHistory()->create([
            'old_price' => $from,
            'new_price' => $to,
            'changed_by' => $changedBy,
            'changed_at' => now(),
        ]);
    }

    /**
     * Check if item is low on stock.
     */
    public function isLowStock(): bool
    {
        if ($this->reorder_level === null) {
            return false;
        }
        
        return $this->stock <= $this->reorder_level;
    }

    /**
     * Scope to filter active items.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter low stock items.
     */
    public function scopeLowStock($query)
    {
        return $query->whereNotNull('reorder_level')
                    ->whereColumn('stock', '<=', 'reorder_level');
    }
}
