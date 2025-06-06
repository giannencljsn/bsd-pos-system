<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'reference',
        'customer_id',
        'customer_name',
        'tax_percentage',
        'tax_amount',
        'discount_percentage',
        'discount_amount',
        'shipping_amount',
        'total_amount',
        'paid_amount',
        'due_amount',
        'status',
        'payment_status',
        'payment_method',
        'note',
    ];

    protected $casts = [
        'date' => 'date',
        'tax_percentage' => 'integer',
        'tax_amount' => 'integer',
        'discount_percentage' => 'integer',
        'discount_amount' => 'integer',
        'shipping_amount' => 'integer',
        'total_amount' => 'integer',
        'paid_amount' => 'integer',
        'due_amount' => 'integer',
    ];
}
