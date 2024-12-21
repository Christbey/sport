<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Stripe\BillingPortal\Configuration;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\SubscriptionItem;

class SubscriptionController extends Controller
{

    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.api.secret'));
    }

    /**
     * Show the subscription plans page.
     */
    public function index()
    {
        $plans = Plan::all();
        $user = auth()->user();

        // Get the current subscription if it exists
        $subscription = $user->subscription('default');

        // Check if user has an active plan
        $isActivePlan = false;
        $currentPlanId = null;

        if ($subscription) {
            // Get the current plan's price ID
            $currentPriceId = $subscription->stripe_price;

            // Find the corresponding plan
            $currentPlan = Plan::where('stripe_price_id', $currentPriceId)->first();

            if ($currentPlan) {
                $currentPlanId = $currentPlan->id;
                $isActivePlan = $subscription->active() || $subscription->onGracePeriod();
            }
        }

        return view('subscriptions.index', compact(
            'plans',
            'subscription',
            'isActivePlan',
            'currentPlanId'
        ));
    }

    /**
     * Handle the subscription checkout process.
     */
    public function checkout(Request $request)
    {
        $plan = Plan::findOrFail($request->plan_id);

        $subscription = $request->user()
            ->newSubscription('default', $plan->stripe_price_id)
            ->allowPromotionCodes();

        // Add any additional subscription items if specified
        if ($request->has('add_ons')) {
            foreach ($request->add_ons as $addOnPriceId => $quantity) {
                $subscription->addPriceItem($addOnPriceId, [
                    'quantity' => $quantity
                ]);
            }
        }

        return $subscription->checkout([
            'success_url' => route('subscription.success', ['session_id' => '{CHECKOUT_SESSION_ID}']),
            'cancel_url' => route('subscription.cancel'),
            'payment_behavior' => 'default_incomplete',
            'collection_method' => 'charge_automatically',
            'allow_promotion_codes' => true,
        ]);
    }

    /**
     * Show the subscription management page.
     */
    public function manage(Request $request)
    {
        try {
            $user = $request->user();
            $subscription = $user->subscription('default');

            // Get all available plans
            $plans = Plan::all();

            // Get current plan
            $currentPlan = null;
            if ($subscription) {
                $currentPlan = $plans->firstWhere('stripe_price_id', $subscription->stripe_price);
            }

            $upcomingInvoice = null;
            try {
                if ($subscription && $subscription->active()) {
                    $upcomingInvoice = $user->upcomingInvoice();
                }
            } catch (Exception $e) {
                report($e);
            }

            // Fallback for next billing date if no upcoming invoice
            $nextBillingDate = null;
            if ($subscription && $subscription->created_at) {
                if ($subscription->onGracePeriod()) {
                    $nextBillingDate = $subscription->ends_at;
                } else {
                    $nextBillingDate = $subscription->created_at->addMonth();
                }
            }

            return view('subscriptions.manage', compact(
                'subscription',
                'upcomingInvoice',
                'plans',
                'currentPlan',
                'nextBillingDate'
            ));

        } catch (Exception $e) {
            report($e);
            return redirect()->route('subscription.index')
                ->with('error', 'Unable to fetch subscription details.');
        }
    }

    /**
     * Cancel the subscription.
     */
    public function cancelSubscription(Request $request)
    {
        $subscription = $request->user()->subscription('default');

        if (!$subscription) {
            return redirect()->route('subscription.manage')
                ->with('error', 'No active subscription found.');
        }

        try {
            // Handle immediate cancellation if requested
            if ($request->input('cancel_immediately', false)) {
                $subscription->cancelNow();
                $message = 'Your subscription has been cancelled immediately.';
            } else {
                // Cancel at period end
                $subscription->cancel([
                    'cancellation_details' => [
                        'comment' => $request->input('cancellation_reason'),
                        'feedback' => $request->input('feedback')
                    ]
                ]);
                $message = 'Your subscription will be cancelled at the end of the billing period.';
            }

            return redirect()->route('subscription.manage')
                ->with('success', $message);

        } catch (ApiErrorException $e) {
            report($e);
            return redirect()->route('subscription.manage')
                ->with('error', 'Unable to cancel subscription. Please try again.');
        }
    }

    /**
     * Handle cancelled checkout.
     */
    public function cancel(Request $request)
    {
        return redirect()
            ->route('subscription.index')
            ->with('warning', 'The subscription process was cancelled.');
    }

    /**
     * Resume a cancelled subscription.
     */
    public function showResume()
    {
        $subscription = auth()->user()->subscription('default');

        if (!$subscription || !$subscription->canceled() || !$subscription->onGracePeriod()) {
            return redirect()->route('subscription.manage')
                ->with('error', 'No subscription eligible for resumption was found.');
        }

        return view('subscriptions.resume-subscription', compact('subscription'));
    }

    public function resumeSubscription(Request $request)
    {
        $subscription = $request->user()->subscription('default');

        if (!$subscription || !$subscription->canceled()) {
            return redirect()->route('subscription.manage')
                ->with('error', 'No cancelled subscription found.');
        }

        if (!$subscription->onGracePeriod()) {
            return redirect()->route('subscription.manage')
                ->with('error', 'This subscription cannot be resumed. Please create a new subscription.');
        }

        try {
            $subscription->resume([
                'proration_behavior' => $request->input('proration_behavior', 'create_prorations'),
                'billing_cycle_anchor' => $request->input('billing_cycle_anchor', 'unchanged')
            ]);

            // Check if we need to handle any incomplete payments
            if ($subscription->hasIncompletePayment()) {
                return redirect()->route('cashier.payment', [
                    $subscription->latestPayment()->id,
                    'redirect' => route('subscription.manage')
                ]);
            }

            return redirect()->route('subscription.manage')
                ->with('success', 'Your subscription has been resumed successfully.');

        } catch (IncompletePayment $exception) {
            return redirect()->route('cashier.payment', [
                $exception->payment->id,
                'redirect' => route('subscription.manage')
            ]);
        } catch (ApiErrorException $e) {
            report($e);
            return redirect()->route('subscription.manage')
                ->with('error', 'Unable to resume subscription: ' . $e->getMessage());
        }
    }

    /**
     * Update the subscription plan.
     */
    public function updatePlan(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'proration_behavior' => 'nullable|in:always_invoice,create_prorations,none'
        ]);

        $user = $request->user();
        $subscription = $user->subscription('default');
        $newPlan = Plan::findOrFail($request->plan_id);

        if (!$subscription) {
            return redirect()->route('subscription.manage')
                ->with('error', 'No active subscription found.');
        }

        try {
            // Update the subscription with proration settings
            $subscription->swap($newPlan->stripe_price_id, [
                'proration_behavior' => $request->input('proration_behavior', 'create_prorations'),
                'payment_behavior' => $request->input('payment_behavior', 'allow_incomplete'),
                'billing_cycle_anchor' => $request->input('billing_cycle_anchor'),
                'items' => [
                    [
                        'id' => $subscription->items->first()->id,
                        'price' => $newPlan->stripe_price_id,
                    ]
                ]
            ]);

            return redirect()->route('subscription.manage')
                ->with('success', 'Your subscription has been updated to ' . $newPlan->name);

        } catch (IncompletePayment $exception) {
            return redirect()->route('cashier.payment', [
                $exception->payment->id,
                'redirect' => route('subscription.manage')
            ]);
        } catch (ApiErrorException $e) {
            report($e);
            return redirect()->route('subscription.manage')
                ->with('error', 'Unable to update subscription. Please try again.');
        }
    }

    /**
     * Add an item to the subscription.
     */
    public function addItem(Request $request)
    {
        $request->validate([
            'price_id' => 'required|string',
            'quantity' => 'required|integer|min:1'
        ]);

        $subscription = $request->user()->subscription('default');

        try {
            $subscription->addPriceItem(
                $request->price_id,
                ['quantity' => $request->quantity],
                ['proration_behavior' => $request->input('proration_behavior', 'create_prorations')]
            );

            return redirect()->route('subscription.manage')
                ->with('success', 'Add-on successfully added to your subscription.');

        } catch (ApiErrorException $e) {
            report($e);
            return redirect()->route('subscription.manage')
                ->with('error', 'Unable to add item to subscription.');
        }
    }

    /**
     * Remove an item from the subscription.
     */
    public function removeItem(Request $request)
    {
        $request->validate([
            'item_id' => 'required|string'
        ]);

        try {
            SubscriptionItem::retrieve($request->item_id)->delete([
                'proration_behavior' => $request->input('proration_behavior', 'create_prorations')
            ]);

            return redirect()->route('subscription.manage')
                ->with('success', 'Item removed from subscription.');

        } catch (ApiErrorException $e) {
            report($e);
            return redirect()->route('subscription.manage')
                ->with('error', 'Unable to remove subscription item.');
        }
    }

    /**
     * Update subscription item quantity.
     */
    public function updateItemQuantity(Request $request)
    {
        $request->validate([
            'item_id' => 'required|string',
            'quantity' => 'required|integer|min:1'
        ]);

        try {
            SubscriptionItem::update($request->item_id, [
                'quantity' => $request->quantity,
                'proration_behavior' => $request->input('proration_behavior', 'create_prorations')
            ]);

            return redirect()->route('subscription.manage')
                ->with('success', 'Quantity updated successfully.');

        } catch (ApiErrorException $e) {
            report($e);
            return redirect()->route('subscription.manage')
                ->with('error', 'Unable to update quantity.');
        }
    }

    /**
     * Handle successful subscription.
     */
    public function success(Request $request)
    {
        $subscription = auth()->user()->subscriptions()->latest()->first();
        return view('subscriptions.success', compact('subscription'));
    }

    /**
     * Show billing portal.
     */
    /**
     * Show billing portal.
     */
    public function billingPortal(Request $request)
    {
        try {
            $configuration = Configuration::create([
                'business_profile' => [
                    'headline' => 'Manage Your Subscription',
                ],
                'features' => [
                    'customer_update' => [
                        'allowed_updates' => ['email', 'address'],
                        'enabled' => true,
                    ],
                    'invoice_history' => ['enabled' => true],
                    'payment_method_update' => ['enabled' => true],
                    'subscription_cancel' => [
                        'enabled' => true,
                        'mode' => 'at_period_end',
                    ],
                    'subscription_update' => [
                        'enabled' => true,
                        'default_allowed_updates' => ['price'],
                    ],
                ],
            ]);

            return $request->user()->redirectToBillingPortal(
                route('subscription.manage'),
                ['configuration' => $configuration->id]
            );

        } catch (Exception $e) {
            report($e);
            return redirect()->route('subscription.manage')
                ->with('error', 'Unable to access billing portal. Please try again.');
        }
    }

    /**
     * Handle adding a new payment method and then changing the plan
     */
    public function addPaymentMethodAndChangePlan(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|string',
            'plan_id' => 'required|exists:plans,id'
        ]);

        try {
            $user = $request->user();

            // Add payment method
            $paymentMethod = $request->payment_method;
            $user->addPaymentMethod($paymentMethod);
            $user->updateDefaultPaymentMethod($paymentMethod);

            // Redirect to change plan with the new payment method
            $request->merge(['plan_id' => $request->plan_id]);
            return $this->changePlan($request);

        } catch (Exception $e) {
            report($e);
            return back()->with('error', 'Unable to add payment method.');
        }
    }

    /**
     * Process the plan change
     */
    public function changePlan(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id'
        ]);

        try {
            $user = $request->user();
            $subscription = $user->subscription('default');
            $newPlan = Plan::findOrFail($request->plan_id);

            if (!$subscription) {
                return redirect()->route('subscription.index')
                    ->with('error', 'No active subscription found.');
            }

            // Perform the plan change
            $subscription->swap($newPlan->stripe_price_id, [
                'proration_behavior' => 'always_invoice',
                'payment_behavior' => 'default_incomplete',
                'billing_cycle_anchor' => 'unchanged'
            ]);

            return redirect()->route('subscription.manage')
                ->with('success', "Successfully changed to {$newPlan->name} plan.");

        } catch (IncompletePayment $exception) {
            return redirect()->route('cashier.payment', [
                $exception->payment->id,
                'redirect' => route('subscription.manage')
            ]);
        } catch (Exception $e) {
            report($e);
            return redirect()->route('subscription.manage')
                ->with('error', 'Unable to change subscription plan. ' . $e->getMessage());
        }
    }

    /**
     * Show the plan change confirmation page with preview
     */
    public function showChangePlan(Request $request)
    {
        $plan_id = $request->query('plan_id');
        if (!$plan_id) {
            return redirect()->route('subscription.manage')
                ->with('error', 'No plan selected.');
        }

        try {
            $user = $request->user();
            $subscription = $user->subscription('default');
            $newPlan = Plan::findOrFail($plan_id);

            if (!$subscription) {
                return redirect()->route('subscription.index')
                    ->with('warning', 'You don\'t have an active subscription.');
            }

            // Check if user has a default payment method
            if (!$user->hasDefaultPaymentMethod()) {
                $intent = $user->createSetupIntent();
                return view('subscriptions.add-payment-method', [
                    'intent' => $intent,
                    'plan' => $newPlan,
                    'planId' => $plan_id
                ]);
            }

            $currentPlan = Plan::where('stripe_price_id', $subscription->stripe_price)->first();
            $proratedCharges = $this->calculateProratedCharges($user, $subscription, $newPlan);

            return view('subscriptions.change-plan', compact(
                'subscription',
                'newPlan',
                'currentPlan',
                'proratedCharges'
            ));

        } catch (Exception $e) {
            report($e);
            return redirect()->route('subscription.manage')
                ->with('error', 'Unable to load plan change page. Please try again.');
        }
    }

    /**
     * Show the plan change confirmation page
     */

    private function calculateProratedCharges($user, $subscription, $newPlan)
    {
        try {
            $stripe = new StripeClient(config('cashier.secret'));

            // Get upcoming invoice preview from Stripe
            $invoice = $stripe->invoices->upcoming([
                'customer' => $user->stripe_id,
                'subscription' => $subscription->stripe_id,
                'subscription_items' => [
                    [
                        'id' => $subscription->items->first()->stripe_id,
                        'price' => $newPlan->stripe_price_id,
                    ],
                ],
                'subscription_proration_behavior' => 'always_invoice',
            ]);

            return [
                'amount' => $invoice->amount_due / 100, // Convert from cents to dollars
                'next_payment_date' => $invoice->next_payment_attempt
                    ? Carbon::createFromTimestamp($invoice->next_payment_attempt)->format('M d, Y')
                    : now()->addDays(30)->format('M d, Y'), // Fallback to 30 days from now if null
                'is_upgrade' => $invoice->amount_due > 0
            ];

        } catch (Exception $e) {
            // Fallback calculation if Stripe preview fails
            $currentPlan = Plan::where('stripe_price_id', $subscription->stripe_price)->first();
            $proratedAmount = abs($newPlan->price - $currentPlan->price);

            return [
                'amount' => $proratedAmount,
                'next_payment_date' => now()->addDays(30)->format('M d, Y'),
                'is_upgrade' => $newPlan->price > $currentPlan->price
            ];
        }
    }

    public function confirmPayment(Request $request)
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
            'amount' => 'required|numeric',
            'card_holder_name' => 'required|string'
        ]);

        try {
            $user = auth()->user();

            // Verify payment intent with Stripe
            $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);

            if ($paymentIntent->status === 'succeeded') {
                // Process the payment (e.g., update subscription, create invoice, etc.)
                // You might want to add more specific logic here based on your business rules

                return redirect()->route('subscription.manage')
                    ->with('success', 'Payment successful!');
            } else {
                return back()->with('error', 'Payment could not be processed.');
            }
        } catch (Exception $e) {
            report($e);
            return back()->with('error', 'An error occurred while processing your payment.');
        }
    }

    public function previewPlanChange(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id'
        ]);

        try {
            $user = $request->user();
            $subscription = $user->subscription('default');
            $newPlan = Plan::findOrFail($request->plan_id);

            $proratedCharges = $this->calculateProratedCharges($user, $subscription, $newPlan);

            return response()->json([
                'success' => true,
                'proration_amount' => $proratedCharges['amount'],
                'next_payment_date' => $proratedCharges['next_payment_date'],
                'is_upgrade' => $proratedCharges['is_upgrade']
            ]);

        } catch (Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Unable to calculate proration amount.'
            ], 422);
        }
    }

    /**
     * Calculate prorated invoice for plan change
     */
    private function calculateProratedInvoice($subscription, $newPlan)
    {
        try {
            // Preview the invoice to get prorated charges
            $preview = $subscription->owner()->previewInvoice([
                'subscription_items' => [
                    [
                        'id' => $subscription->items->first()->id,
                        'price' => $newPlan->stripe_price_id,
                    ],
                ],
                'subscription_proration_behavior' => 'always_invoice',
            ]);

            return [
                'amount' => $preview->amount_due, // in cents
                'next_payment_date' => Carbon::createFromTimestamp($preview->next_payment_attempt)->format('M d, Y')
            ];
        } catch (Exception $e) {
            // Fallback to a simple calculation if preview fails
            $currentPlan = Plan::where('stripe_price_id', $subscription->stripe_price)->first();
            $proratedAmount = abs($newPlan->price - $currentPlan->price);

            return [
                'amount' => (int)($proratedAmount * 100), // convert to cents
                'next_payment_date' => now()->addDays(30)->format('M d, Y')
            ];
        }
    }
    /**
     * Show the plan change confirmation page
     */

    /**
     * Generate a success message for plan change
     */
    private function getPlanChangeSuccessMessage($newPlan, $proratedInvoice)
    {
        $baseMessage = "Successfully changed to {$newPlan->name} plan.";

        if ($proratedInvoice['amount'] > 0) {
            $proratedAmount = number_format($proratedInvoice['amount'] / 100, 2);
            $baseMessage .= " A prorated charge of ${$proratedAmount} will be applied. Next payment date: {$proratedInvoice['next_payment_date']}.";
        }

        return $baseMessage;
    }

    /**
     * Get products configuration for billing portal.
     */
    private function getPortalProducts()
    {
        $products = [];
        $plans = Plan::all();

        foreach ($plans as $plan) {
            if (!$plan->stripe_product_id) continue;

            $products[$plan->stripe_product_id] = [
                'prices' => [$plan->stripe_price_id],
            ];
        }

        return $products;
    }
}