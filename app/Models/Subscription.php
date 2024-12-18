<?php

namespace App\Models;

use Laravel\Cashier\Subscription as CashierSubscription;

class Subscription extends CashierSubscription
{
    /**
     * Get the plan associated with the subscription.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class, 'stripe_price', 'stripe_price_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
