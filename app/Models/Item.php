<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
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
     * Check if item is active and available for requisition.
     */
    public function isAvailable(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if stock is low (below reorder level).
     */
    public function isLowStock(): bool
    {
        if (is_null($this->stock) || is_null($this->reorder_level)) {
            return false;
        }
        
        return $this->stock <= $this->reorder_level;
    }

    /**
     * Scope to get only active items.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get items by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get low stock items.
     */
    public function scopeLowStock($query)
    {
        return $query->whereNotNull('stock')
                    ->whereNotNull('reorder_level')
                    ->whereRaw('stock <= reorder_level');
    }

    /**
     * Log price change to history.
     */
    public function logPriceChange(float $oldPrice, float $newPrice, ?string $changedBy = null): void
    {
        $this->priceHistory()->create([
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'changed_by' => $changedBy,
            'changed_at' => now(),
        ]);
    }
}
