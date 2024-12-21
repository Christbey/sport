<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class Plan extends Model
{
    private const FREE_LIMIT = 10;
    private const PREMIUM_LIMIT = 50;

    // Example within the class
    private const PRO_LIMIT = 100;
    protected $fillable = [
        'name',
        'stripe_price_id',
        'price',
        'currency',
        'active',
        'interval',
        'interval_count',

    ];
    protected $casts = [
        'price' => 'decimal:2'
    ];

    public function getLimit(): int
    {
        return $this->stripe_price_id === 'price_1JZG3e2eZvKYlo2C' ? self::FREE_LIMIT : self::PREMIUM_LIMIT;
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }


}