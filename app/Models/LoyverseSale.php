<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoyverseSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'loyverse_receipt_number',
        'sale_date',
        'total_amount',
        'tax_amount',
        'payment_method',
        'store_name',
        'line_items',
        'raw_data',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'total_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_items' => 'array',
    ];
}
