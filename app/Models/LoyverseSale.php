<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoyverseSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'date',
        'total_sales',
        'tax',
        'discount',
        'items',
        'payment_type',
        'customer_name',
        'receipt_number',
        'created_at_external',
        'synced_at',
    ];

    protected $casts = [
        'date' => 'date',
        'total_sales' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'items' => 'array',
        'created_at_external' => 'datetime',
        'synced_at' => 'datetime',
    ];
}
