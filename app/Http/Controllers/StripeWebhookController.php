<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Spatie\Permission\Models\Role;
use Stripe\Subscription;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends CashierWebhookController
{
    public function handleWebhook(Request $request): Response
    {
        //$this->logWebhook($request);
        return parent::handleWebhook($request);
    }

//    private function logWebhook(Request $request): void
//    {
//        Log::info('Stripe webhook received', [
//            'event' => $request->input('type'),
//            'payload' => $request->all()
//        ]);
//    }

    protected function handleCustomerSubscriptionDeleted(array $payload): Response
    {
        if ($user = $this->getUserFromPayload($payload)) {
            Log::info('Subscription cancelled', [
                'user_id' => $user->id,
                'subscription_id' => $payload['data']['object']['id']
            ]);

            // The observer will handle role changes when the subscription is updated
        }

        return parent::handleCustomerSubscriptionDeleted($payload);
    }

    private function getUserFromPayload(array $payload): ?object
    {
        $customerId = $payload['data']['object']['customer'] ?? null;
        return $this->getUserByStripeId($customerId);
    }

    protected function handleCustomerSubscriptionCreated(array $payload): Response
    {
        try {
            $user = $this->getUserFromPayload($payload);

            if (!$user) {
                Log::warning('No user found for subscription creation', [
                    'customer_id' => $payload['data']['object']['customer'] ?? 'Unknown',
                    'subscription_id' => $payload['data']['object']['id'] ?? 'Unknown'
                ]);
                return $this->successMethod();
            }

            // Check for duplicate active subscriptions
            $activeSubscriptions = $user->subscriptions()
                ->whereIn('stripe_status', ['active', 'trialing'])
                ->get();

            if ($activeSubscriptions->count() > 0) {
                Log::warning('Multiple active subscriptions found', [
                    'user_id' => $user->id,
                    'new_subscription_id' => $payload['data']['object']['id'],
                    'active_subscriptions_count' => $activeSubscriptions->count()
                ]);

                // Cancel the new subscription via Stripe API directly
                $newSubscriptionId = $payload['data']['object']['id'];

                try {
                    Subscription::update($newSubscriptionId, [
                        'cancel_at_period_end' => true
                    ]);

                    Log::info('New duplicate subscription marked to cancel at period end', [
                        'user_id' => $user->id,
                        'subscription_id' => $newSubscriptionId
                    ]);
                } catch (Exception $stripeError) {
                    Log::error('Failed to cancel duplicate subscription', [
                        'user_id' => $user->id,
                        'subscription_id' => $newSubscriptionId,
                        'error' => $stripeError->getMessage()
                    ]);
                }

                return $this->successMethod();
            }

            Log::info('Subscription created', [
                'user_id' => $user->id,
                'subscription_id' => $payload['data']['object']['id'],
                'price_id' => $payload['data']['object']['items']['data'][0]['price']['id'] ?? null
            ]);

            return parent::handleCustomerSubscriptionCreated($payload);
        } catch (Exception $e) {
            Log::error('Error in handleCustomerSubscriptionCreated', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->successMethod();
        }
    }

    protected function handleCustomerSubscriptionUpdated(array $payload): Response
    {
        if ($user = $this->getUserFromPayload($payload)) {
            $subscriptionData = $payload['data']['object'];

            Log::info('Subscription updated', [
                'user_id' => $user->id,
                'subscription_id' => $subscriptionData['id'],
                'status' => $subscriptionData['status'],
                'price_id' => $subscriptionData['items']['data'][0]['price']['id'] ?? null
            ]);

            // Handle role update based on the new price
            $this->updateUserRole($user, $subscriptionData);
        }

        return parent::handleCustomerSubscriptionUpdated($payload);
    }

    private function updateUserRole($user, array $subscriptionData)
    {
        try {
            // Get the new price ID
            $newStripePriceId = $subscriptionData['items']['data'][0]['price']['id'] ?? null;

            if (!$newStripePriceId) {
                Log::warning('No price ID found in subscription update', [
                    'user_id' => $user->id,
                    'subscription_id' => $subscriptionData['id']
                ]);
                return;
            }

            // Direct role ID mapping
            $roleMapping = [
                'price_1QYI1qELTH1Vz3ILtAIQdjOB' => 9,  // Pro User
                'price_1QYHzyELTH1Vz3ILkF46LZkA' => 7 // Free User
            ];

            // Get the role ID
            $roleId = $roleMapping[$newStripePriceId] ?? null;

            if (!$roleId) {
                Log::warning('No role mapping found for stripe price', [
                    'user_id' => $user->id,
                    'stripe_price_id' => $newStripePriceId,
                    'available_mappings' => $roleMapping
                ]);
                return;
            }

            // Sync the user's roles
            $user->roles()->sync([$roleId]);

            // Fetch the role name for logging
            $roleName = Role::find($roleId)->name ?? 'Unknown Role';

            Log::info('User role updated via Stripe webhook', [
                'user_id' => $user->id,
                'email' => $user->email,
                'new_role_id' => $roleId,
                'new_role_name' => $roleName,
                'new_stripe_price_id' => $newStripePriceId
            ]);
        } catch (Exception $e) {
            Log::error('Error updating user role', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    protected function handleInvoicePaymentFailed(array $payload): Response
    {
        if ($user = $this->getUserFromPayload($payload)) {
            Log::error('Payment failed', [
                'user_id' => $user->id,
                'invoice_id' => $payload['data']['object']['id'],
                'amount' => $payload['data']['object']['amount_due']
            ]);

            // You might want to send an email here
            // Mail::to($user)->send(new PaymentFailed($user));
        }

        return $this->successMethod();
    }

    protected function handleInvoicePaymentSucceeded(array $payload): Response
    {
        if ($user = $this->getUserFromPayload($payload)) {
            Log::info('Payment succeeded', [
                'user_id' => $user->id,
                'invoice_id' => $payload['data']['object']['id'],
                'amount' => $payload['data']['object']['amount_paid'],
                'subscription_id' => $payload['data']['object']['subscription']
            ]);
        }

        return $this->successMethod();
    }

    private function hasActiveSubscription($user): bool
    {
        return $user->subscriptions()
            ->whereIn('stripe_status', ['active', 'trialing'])
            ->exists();
    }
}