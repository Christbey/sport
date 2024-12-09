<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index()
    {
        $plans = Plan::all();
        return view('subscriptions.index', compact('plans'));
    }


    public function checkout(Request $request)
    {
        $plan = Plan::findOrFail($request->input('plan_id'));

        if (!$request->user()->hasStripeId()) {
            $request->user()->createAsStripeCustomer();
        }

        return $request->user()->newSubscription('default', $plan->stripe_price_id)
            ->checkout([
                'success_url' => route('subscription.success'),
                'cancel_url' => route('subscription.index'),
            ]);
    }

}
