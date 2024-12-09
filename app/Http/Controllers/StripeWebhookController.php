<?php
// app/Http/Controllers/WebhookController.php
namespace App\Http\Controllers;

use Carbon\Carbon;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;
use Stripe\Subscription;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends CashierController
{
    /**
     * Handle customer subscription updated.
     *
     * @param array $payload
     * @return Response
     */
    protected function handleCustomerSubscriptionUpdated(array $payload)
    {
        if ($user = $this->getUserByStripeId($payload['data']['object']['customer'])) {
            $data = $payload['data']['object'];

            $subscription = $user->subscriptions()->firstOrNew(['stripe_id' => $data['id']]);

            // Handle subscription updates
            if (isset($data['status']) && $data['status'] === Subscription::STATUS_INCOMPLETE_EXPIRED) {
                $subscription->items()->delete();
                $subscription->delete();
                return $this->successMethod();
            }

            // Update subscription details
            $subscription->stripe_status = $data['status'];
            $subscription->quantity = $data['items']['data'][0]['quantity'] ?? null;
            $subscription->stripe_price = $data['items']['data'][0]['price']['id'] ?? null;

            // Handle trial periods
            if (isset($data['trial_end'])) {
                $subscription->trial_ends_at = $data['trial_end'] ?
                    Carbon::createFromTimestamp($data['trial_end']) : null;
            }

            // Handle cancellation
            if (isset($data['cancel_at_period_end'])) {
                if ($data['cancel_at_period_end']) {
                    $subscription->ends_at = Carbon::createFromTimestamp($data['current_period_end']);
                } else {
                    $subscription->ends_at = null;
                }
            }

            $subscription->save();
        }

        return $this->successMethod();
    }

    /**
     * Handle customer subscription deleted.
     *
     * @param array $payload
     * @return Response
     */
    protected function handleCustomerSubscriptionDeleted(array $payload)
    {
        if ($user = $this->getUserByStripeId($payload['data']['object']['customer'])) {
            $user->subscriptions->filter(function ($subscription) use ($payload) {
                return $subscription->stripe_id === $payload['data']['object']['id'];
            })->each(function ($subscription) {
                $subscription->markAsCanceled();
            });
        }

        return $this->successMethod();
    }

    /**
     * Handle payment action required.
     *
     * @param array $payload
     * @return Response
     */
    protected function handleInvoicePaymentActionRequired(array $payload)
    {
        if ($user = $this->getUserByStripeId($payload['data']['object']['customer'])) {
            $subscription = $user->subscription('default');

            if ($subscription) {
                $subscription->stripe_status = Subscription::STATUS_INCOMPLETE;
                $subscription->save();
            }
        }

        return $this->successMethod();
    }
}