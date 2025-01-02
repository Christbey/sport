<?php

namespace App\Console\Commands;

use App\Models\Plan;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class SyncStripePlans extends Command
{
    protected $signature = 'stripe:sync-plans {--force : Force sync even inactive prices}';
    protected $description = 'Synchronize Stripe prices with the local plans table';
    private StripeClient $stripe;

    public function __construct()
    {
        parent::__construct();
        $this->stripe = new StripeClient(config('cashier.secret'));
    }

    public function handle()
    {
        $this->info('Starting Stripe plans synchronization...');

        try {
            $prices = $this->retrieveStripePrices();
            $this->info(sprintf('Found %d prices in Stripe.', count($prices->data)));

            $this->syncPrices($prices->data);
            $this->deactivateRemovedPlans($prices->data);

            $this->info('Plans synchronization completed successfully!');
        } catch (Exception $e) {
            $this->error('Failed to sync plans: ' . $e->getMessage());
            Log::error('Stripe plans sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function retrieveStripePrices()
    {
        $params = [
            'expand' => ['data.product', 'data.recurring'],
            'limit' => 100,
        ];

        // Remove 'active' if forced sync is requested
        if (!$this->option('force')) {
            $params['active'] = true;
        }

        return $this->stripe->prices->all($params);
    }

    private function syncPrices(array $prices)
    {
        $bar = $this->output->createProgressBar(count($prices));
        $bar->start();

        $syncedCount = 0;
        $errorCount = 0;

        foreach ($prices as $price) {
            try {
                Plan::updateOrCreate(
                    ['stripe_price_id' => $price->id],
                    [
                        'name' => $price->product->name ?? 'Unknown Product',
                        'price' => $price->unit_amount / 100,
                        'currency' => $price->currency,
                        'active' => $price->active ? 1 : 0,
                        'interval' => $price->recurring->interval ?? 'month',
                        'interval_count' => $price->recurring->interval_count ?? 1,
                    ]
                );
                $syncedCount++;
            } catch (Exception $e) {
                $errorCount++;
                Log::error('Failed to sync individual price', [
                    'price_id' => $price->id,
                    'error' => $e->getMessage(),
                ]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info(sprintf(
            'Synced %d plans successfully, %d failures.',
            $syncedCount,
            $errorCount
        ));
    }

    private function deactivateRemovedPlans(array $activePrices)
    {
        if (!$this->confirm('Deactivate plans that no longer exist in Stripe?')) {
            return;
        }

        $activeStripeIds = collect($activePrices)->pluck('id')->toArray();

        $deactivatedCount = Plan::whereNotIn('stripe_price_id', $activeStripeIds)
            ->where('active', true)
            ->update(['active' => false]);

        if ($deactivatedCount > 0) {
            $this->info("Deactivated {$deactivatedCount} plans.");
            Log::info('Deactivated plans during sync', [
                'count' => $deactivatedCount,
            ]);
        }
    }
}
