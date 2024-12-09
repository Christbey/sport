<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'stripe_price_id',
        'price',
        'currency'
    ];

    protected $casts = [
        'price' => 'decimal:2'
    ];
}