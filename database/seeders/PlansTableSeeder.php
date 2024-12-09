<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlansTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $plans = [
            [
                'name' => 'Basic',
                'stripe_price_id' => 'price_1Hh1XYZ...', // Replace with actual Stripe Price ID
                'price' => 10.00,
                'currency' => 'usd',
            ],
            [
                'name' => 'Pro',
                'stripe_price_id' => 'price_1Hh1ABC...', // Replace with actual Stripe Price ID
                'price' => 20.00,
                'currency' => 'usd',
            ],
            [
                'name' => 'Enterprise',
                'stripe_price_id' => 'price_1Hh1DEF...', // Replace with actual Stripe Price ID
                'price' => 30.00,
                'currency' => 'usd',
            ],
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }
    }
}
