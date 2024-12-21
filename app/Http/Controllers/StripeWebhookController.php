<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends CashierWebhookController
{
    public function handleWebhook(Request $request): Response
    {
        $this->logWebhook($request);
        return parent::handleWebhook($request);
    }

    private function logWebhook(Request $request): void
    {
        Log::info('Stripe webhook received', [
            'event' => $request->input('type'),
            'payload' => $request->all()
        ]);
    }

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
        if ($user = $this->getUserFromPayload($payload)) {
            // Check for duplicate active subscriptions
            if ($this->hasActiveSubscription($user)) {
                Log::warning('Attempted to create multiple subscriptions', [
                    'user_id' => $user->id,
                    'subscription_id' => $payload['data']['object']['id']
                ]);

                try {
                    $user->subscription($payload['data']['object']['id'])->cancelNow();
                } catch (Exception $e) {
                    Log::error('Failed to cancel duplicate subscription', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }

                return $this->successMethod();
            }

            Log::info('Subscription created', [
                'user_id' => $user->id,
                'subscription_id' => $payload['data']['object']['id'],
                'price_id' => $payload['data']['object']['items']['data'][0]['price']['id'] ?? null
            ]);
        }

        return parent::handleCustomerSubscriptionCreated($payload);
    }

    private function hasActiveSubscription($user): bool
    {
        return $user->subscriptions()
            ->whereIn('stripe_status', ['active', 'trialing'])
            ->exists();
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

            // Find the corresponding plan
            $plan = Plan::where('stripe_price_id', $newStripePriceId)->first();

            if (!$plan) {
                Log::warning('No matching plan found for stripe price ID', [
                    'user_id' => $user->id,
                    'stripe_price_id' => $newStripePriceId
                ]);
                return;
            }

            // Determine role based on plan name
            $roleName = match ($plan->name) {
                'pro_user' => 'Pro User',
                default => 'free_user'
            };

            // Find the corresponding role
            $role = Role::where('name', $roleName)->first();

            if (!$role) {
                Log::warning('Role not found', [
                    'user_id' => $user->id,
                    'role_name' => $roleName
                ]);
                return;
            }

            // Sync the user's roles
            $user->roles()->sync([$role->id]);

            Log::info('User role updated via Stripe webhook', [
                'user_id' => $user->id,
                'email' => $user->email,
                'new_role' => $roleName,
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
}