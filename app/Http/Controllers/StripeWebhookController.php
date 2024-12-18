<?php

namespace App\Http\Controllers;

use App\Mail\PaymentFailed;
use App\Mail\SubscriptionCancelled;
use App\Mail\SubscriptionCreated;
use App\Models\Subscription;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Log};
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends CashierWebhookController
{
    // Define subscription plan mappings
    private const SUBSCRIPTION_ROLES = [
        'price_basic' => 'basic_subscriber',
        'price_pro' => 'pro_subscriber',
        'price_enterprise' => 'enterprise_subscriber'
    ];

    private const SUBSCRIPTION_PERMISSIONS = [
        'price_basic' => [
            'access_basic_features',
            'create_basic_content'
        ],
        'price_pro' => [
            'access_basic_features',
            'access_pro_features',
            'create_basic_content',
            'create_pro_content'
        ],
        'price_enterprise' => [
            'access_basic_features',
            'access_pro_features',
            'access_enterprise_features',
            'create_basic_content',
            'create_pro_content',
            'create_enterprise_content'
        ]
    ];

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
        $response = parent::handleCustomerSubscriptionDeleted($payload);

        if ($user = $this->getUserFromPayload($payload)) {
            $this->handleSubscriptionCancelled($user, $payload['data']['object']);
        }

        return $response;
    }

    private function getUserFromPayload(array $payload): ?object
    {
        $customerId = $payload['data']['object']['customer'] ?? null;
        return $this->getUserByStripeId($customerId);
    }

    private function handleSubscriptionCancelled($user, array $subscription): void
    {
        Log::info('Subscription cancelled', [
            'user_id' => $user->id,
            'subscription_id' => $subscription['id']
        ]);

        try {
            // Update subscription status in your custom Subscription model
            $this->updateSubscriptionStatus($user, $subscription['id'], 'cancelled');

            // Remove subscription-related roles and permissions
            $this->removeSubscriptionPrivileges($user);

            // Send cancellation email
            #Mail::to($user)->send(new SubscriptionCancelled($user));
        } catch (Exception $e) {
            Log::error('Failed to process subscription cancellation', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function updateSubscriptionStatus($user, string $subscriptionId, string $status): void
    {
        $subscription = Subscription::where('stripe_id', $subscriptionId)->first();

        if ($subscription) {
            $subscription->update([
                'stripe_status' => $status,
                'ends_at' => $status === 'cancelled' ? now() : $subscription->ends_at,
                // Update other fields as necessary
            ]);

            Log::info('Updated subscription status', [
                'user_id' => $user->id,
                'subscription_id' => $subscriptionId,
                'new_status' => $status
            ]);
        } else {
            Log::warning('Subscription not found for status update', [
                'user_id' => $user->id,
                'subscription_id' => $subscriptionId
            ]);
        }
    }

    private function removeSubscriptionPrivileges($user): void
    {
        // Remove subscription-specific roles
        foreach (self::SUBSCRIPTION_ROLES as $roleName) {
            if ($user->hasRole($roleName)) {
                $user->removeRole($roleName);
            }
        }

        // Optionally revoke permissions directly if necessary
        // This depends on how permissions are structured in your application

        Log::info('Removed subscription privileges', [
            'user_id' => $user->id
        ]);
    }

    protected function handleCustomerSubscriptionCreated(array $payload): Response
    {
        if ($user = $this->getUserFromPayload($payload)) {
            // Check if user already has an active subscription
            if ($this->hasActiveSubscription($user)) {
                Log::warning('Attempted to create multiple subscriptions', [
                    'user_id' => $user->id,
                    'subscription_id' => $payload['data']['object']['id']
                ]);

                // Optionally cancel the new subscription
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

            // Handle subscription creation
            $this->handleSubscriptionCreated($user, $payload['data']['object']);
        }

        return parent::handleCustomerSubscriptionCreated($payload);
    }

    private function hasActiveSubscription($user): bool
    {
        return $user->subscriptions()
            ->whereIn('stripe_status', ['active', 'trialing'])
            ->exists();
    }

    private function handleSubscriptionCreated($user, array $subscription): void
    {
        $priceId = $subscription['items']['data'][0]['price']['id'] ?? null;

        Log::info('Subscription created', [
            'user_id' => $user->id,
            'subscription_id' => $subscription['id'],
            'price_id' => $priceId
        ]);

        try {
            // Create or update the subscription in your custom Subscription model
            Subscription::updateOrCreate(
                ['stripe_id' => $subscription['id']],
                [
                    'user_id' => $user->id,
                    'stripe_status' => $subscription['status'],
                    'stripe_price' => $priceId,
                    'quantity' => $subscription['quantity'] ?? 1,
                    'ends_at' => $subscription['cancel_at_period_end'] ? now()->addSeconds($subscription['cancel_at']) : null,
                ]
            );

            // Assign roles and permissions based on subscription level
            $this->assignSubscriptionPrivileges($user, $priceId);

            // Send subscription creation email
            #Mail::to($user)->send(new SubscriptionCreated($user));
        } catch (Exception $e) {
            Log::error('Failed to process subscription creation', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function assignSubscriptionPrivileges($user, ?string $priceId): void
    {
        if (!$priceId || !isset(self::SUBSCRIPTION_ROLES[$priceId])) {
            Log::warning('Invalid price ID for role assignment', [
                'user_id' => $user->id,
                'price_id' => $priceId
            ]);
            return;
        }

        // Remove existing subscription roles and permissions
        $this->removeSubscriptionPrivileges($user);

        // Assign new role
        $roleName = self::SUBSCRIPTION_ROLES[$priceId];
        $role = Role::firstOrCreate(['name' => $roleName]);
        $user->assignRole($role);

        // Assign permissions for the subscription level
        if (isset(self::SUBSCRIPTION_PERMISSIONS[$priceId])) {
            foreach (self::SUBSCRIPTION_PERMISSIONS[$priceId] as $permissionName) {
                $permission = Permission::firstOrCreate(['name' => $permissionName]);
                if (!$role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }

        Log::info('Assigned subscription privileges', [
            'user_id' => $user->id,
            'role' => $roleName,
            'permissions' => self::SUBSCRIPTION_PERMISSIONS[$priceId] ?? []
        ]);
    }

    protected function handleCustomerSubscriptionUpdated(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionUpdated($payload);

        if ($user = $this->getUserFromPayload($payload)) {
            $this->handleSubscriptionUpdated($user, $payload['data']['object']);
        }

        return $response;
    }

    private function handleSubscriptionUpdated($user, array $subscription): void
    {
        $priceId = $subscription['items']['data'][0]['price']['id'] ?? null;

        Log::info('Subscription updated', [
            'user_id' => $user->id,
            'subscription_id' => $subscription['id'],
            'status' => $subscription['status'],
            'price_id' => $priceId
        ]);

        try {
            // Update subscription status in your custom Subscription model
            $this->updateSubscriptionStatus($user, $subscription['id'], $subscription['status']);

            switch ($subscription['status']) {
                case 'past_due':
                    // Optionally restrict some permissions
                    $this->restrictSubscriptionPrivileges($user);
                    break;
                case 'active':
                    // Update roles and permissions for new plan
                    $this->assignSubscriptionPrivileges($user, $priceId);
                    break;
                case 'canceled':
                case 'unpaid':
                    $this->handleSubscriptionCancelled($user, $subscription);
                    break;
                // Add more cases as needed
            }
        } catch (Exception $e) {
            Log::error('Failed to process subscription update', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function restrictSubscriptionPrivileges($user): void
    {
        // Example: Temporarily restrict certain permissions while maintaining the role
        $restrictedPermissions = [
            'create_pro_content',
            'create_enterprise_content'
        ];

        foreach ($restrictedPermissions as $permission) {
            if ($user->hasPermissionTo($permission)) {
                $user->revokePermissionTo($permission);
            }
        }

        Log::info('Restricted subscription privileges', [
            'user_id' => $user->id,
            'restricted_permissions' => $restrictedPermissions
        ]);
    }

    protected function handleInvoicePaymentFailed(array $payload): Response
    {
        if ($user = $this->getUserFromPayload($payload)) {
            $this->handlePaymentFailed($user, $payload['data']['object']);
        }

        return $this->successMethod();
    }

    private function handlePaymentFailed($user, array $invoice): void
    {
        Log::error('Payment failed', [
            'user_id' => $user->id,
            'invoice_id' => $invoice['id'],
            'amount' => $invoice['amount_due']
        ]);

        try {
            // Optionally restrict permissions on payment failure
            $this->restrictSubscriptionPrivileges($user);

            // Send payment failure email
            #Mail::to($user)->send(new PaymentFailed($user));
        } catch (Exception $e) {
            Log::error('Failed to process payment failure', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function handleInvoicePaymentSucceeded(array $payload): Response
    {
        if ($user = $this->getUserFromPayload($payload)) {
            $this->handlePaymentSucceeded($user, $payload['data']['object']);
        }

        return $this->successMethod();
    }

    private function handlePaymentSucceeded($user, array $invoice): void
    {
        Log::info('Payment succeeded', [
            'user_id' => $user->id,
            'invoice_id' => $invoice['id'],
            'amount' => $invoice['amount_paid'],
            'subscription_id' => $invoice['subscription']
        ]);

        try {
            // Optionally restore permissions if previously restricted
            $this->restoreSubscriptionPrivileges($user);

            // You can also update subscription records if needed
            $subscription = Subscription::where('stripe_id', $invoice['subscription'])->first();
            if ($subscription) {
                $subscription->update([
                    'last_payment_at' => now(),
                    // Add other fields as necessary
                ]);
            }
        } catch (Exception $e) {
            Log::error('Failed to process payment success', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function restoreSubscriptionPrivileges($user): void
    {
        // Example: Restore previously restricted permissions based on current roles
        foreach (self::SUBSCRIPTION_PERMISSIONS as $priceId => $permissions) {
            foreach ($permissions as $permissionName) {
                if ($user->can($permissionName)) {
                    continue; // Already has permission via role
                }

                // Grant permission if any of the user's roles include it
                foreach ($user->roles as $role) {
                    if ($role->hasPermissionTo($permissionName)) {
                        $user->givePermissionTo($permissionName);
                        Log::info('Restored permission', [
                            'user_id' => $user->id,
                            'permission' => $permissionName
                        ]);
                    }
                }
            }
        }
    }
}
