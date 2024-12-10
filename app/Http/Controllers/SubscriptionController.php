<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Show the subscription plans page.
     */
    public function index()
    {
        $plans = Plan::all(); // Assuming you have a Plan model
        return view('subscriptions.index', compact('plans'));
    }

    /**
     * Handle the subscription checkout process.
     */
    public function checkout(Request $request)
    {
        $plan = Plan::findOrFail($request->plan_id);

        return $request->user()
            ->newSubscription('default', $plan->stripe_price_id)
            ->allowPromotionCodes()
            ->checkout([
                'success_url' => url('/subscriptions/success?session_id={CHECKOUT_SESSION_ID}'),
                'cancel_url' => url('/subscriptions/cancel'), // Updated to plural
            ]);
    }

    public function cancel(Request $request)
    {
        return redirect()
            ->route('subscription.index') // This will redirect to /subscriptions
            ->with('warning', 'The subscription process was cancelled.');
    }


    public function success(Request $request)
    {
        $subscription = auth()->user()->subscriptions()->latest()->first();
        return view('subscriptions.success', compact('subscription'));
    }


}