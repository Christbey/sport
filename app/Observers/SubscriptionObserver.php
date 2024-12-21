<?php

namespace App\Observers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class SubscriptionObserver
{
    public function updated(Subscription $subscription)
    {
        // Only proceed if stripe_price has changed
        if ($subscription->isDirty('stripe_price')) {
            Log::channel('daily')->info('Subscription Stripe Price Updated', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'new_stripe_price' => $subscription->stripe_price
            ]);

            // Find the corresponding plan based on stripe_price_id
            $plan = Plan::where('stripe_price_id', $subscription->stripe_price)->first();

            if (!$plan) {
                Log::channel('daily')->warning('No matching plan found', [
                    'stripe_price' => $subscription->stripe_price
                ]);
                return;
            }

            // Find the user
            $user = User::find($subscription->user_id);

            if (!$user) {
                Log::channel('daily')->warning('User not found', [
                    'user_id' => $subscription->user_id
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
                Log::channel('daily')->warning('Role not found', [
                    'role_name' => $roleName
                ]);
                return;
            }

            // Sync the user's roles
            $user->roles()->sync([$role->id]);

            Log::channel('daily')->info('User role updated', [
                'user_id' => $user->id,
                'new_role' => $roleName
            ]);
        }
    }
}