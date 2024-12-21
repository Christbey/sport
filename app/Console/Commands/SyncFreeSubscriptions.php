<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Subscription;
use Laravel\Cashier\SubscriptionItem;
use Stripe\StripeClient;

class SyncFreeSubscriptions extends Command
{
    protected $signature = 'subscriptions:sync-free';
    protected $description = 'Create free subscriptions for users without subscriptions';

    private StripeClient $stripe;

    public function __construct()
    {
        parent::__construct();
        $this->stripe = new StripeClient(config('cashier.secret'));
    }

    public function handle()
    {
        $this->info('Starting free subscription sync...');

        // Get the free plan
        $freePlan = Plan::where('name', 'Free User')
            ->where('active', 1)
            ->first();

        if (!$freePlan) {
            $this->error('Free plan not found!');
            return Command::FAILURE;
        }

        // Get users without subscriptions
        $users = User::whereDoesntHave('subscriptions')->get();
        $total = $users->count();

        $this->info("Found {$total} users without subscriptions.");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($users as $user) {
            try {
                DB::beginTransaction();

                // Ensure user has a Stripe customer ID
                if (!$user->stripe_id) {
                    $customer = $this->stripe->customers->create([
                        'email' => $user->email,
                        'name' => $user->name,
                        'metadata' => [
                            'user_id' => $user->id
                        ]
                    ]);
                    $user->stripe_id = $customer->id;
                    $user->save();
                }

                // Create Stripe subscription
                $stripeSubscription = $this->stripe->subscriptions->create([
                    'customer' => $user->stripe_id,
                    'items' => [
                        ['price' => $freePlan->stripe_price_id],
                    ],
                    'metadata' => [
                        'user_id' => $user->id
                    ]
                ]);

                // Create local subscription
                $subscription = Subscription::create([
                    'user_id' => $user->id,
                    'type' => 'default', // Using type instead of name for Laravel 11
                    'stripe_id' => $stripeSubscription->id,
                    'stripe_status' => $stripeSubscription->status,
                    'stripe_price' => $freePlan->stripe_price_id,
                    'quantity' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Create subscription item
                SubscriptionItem::create([
                    'subscription_id' => $subscription->id,
                    'stripe_id' => $stripeSubscription->items->data[0]->id,
                    'stripe_product' => $stripeSubscription->items->data[0]->price->product,
                    'stripe_price' => $freePlan->stripe_price_id,
                    'quantity' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                DB::commit();
                $bar->advance();

            } catch (Exception $e) {
                DB::rollBack();
                $this->error("\nFailed to process user {$user->id}: {$e->getMessage()}");
                $this->line($e->getTraceAsString());
                continue;
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Free subscription sync completed!');

        return Command::SUCCESS;
    }
}