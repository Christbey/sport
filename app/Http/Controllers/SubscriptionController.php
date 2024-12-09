<?php

// app/Http/Controllers/SubscriptionController.php
namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Cashier\Exceptions\IncompletePayment;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function index()
    {
        $plans = Plan::all();
        $subscription = auth()->user()->subscription('default');

        return view('subscriptions.index', compact('plans', 'subscription'));
    }

    public function checkout(Request $request)
    {
        $plan = Plan::findOrFail($request->input('plan_id'));

        if (!$request->user()->hasStripeId()) {
            $request->user()->createAsStripeCustomer();
        }

        try {
            return $request->user()
                ->newSubscription('default', $plan->stripe_price_id)
                ->checkout([
                    'success_url' => route('subscriptions.success'),
                    'cancel_url' => route('subscription.index'),
                    'automatic_tax' => [
                        'enabled' => false
                    ],
                    'client_reference_id' => $request->user()->id,
                    'metadata' => [
                        'plan_id' => $plan->id
                    ]
                ]);
        } catch (IncompletePayment $exception) {
            return redirect()->route('cashier.payment', [
                $exception->payment->id,
                'redirect' => route('subscription.index')
            ]);
        }
    }

    public function success(Request $request)
    {
        return view('subscriptions.success');
    }

    public function cancel(Request $request)
    {
        $request->validate([
            'subscription_id' => 'required'
        ]);

        $subscription = $request->user()->subscription('default');

        if ($subscription && $subscription->stripe_id === $request->subscription_id) {
            $subscription->cancel();
        }

        return redirect()->route('subscription.index')
            ->with('success', 'Your subscription has been cancelled.');
    }
}
