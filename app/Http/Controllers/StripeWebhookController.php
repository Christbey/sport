<?php

namespace App\Http\Controllers;

#use App\Mail\PaymentFailed;
#use App\Mail\SubscriptionCancelled;
#use App\Mail\SubscriptionCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends CashierWebhookController
{
    /**
     * Handle subscription created.
     *
     * @param array $payload
     * @return Response
     */

    public function handleWebhook(Request $request)
    {
        Log::info('Webhook received', [
            'payload' => $request->all(),
            'headers' => $request->headers->all()
        ]);

        return parent::handleWebhook($request);
    }

    protected function handleCustomerSubscriptionCreated(array $payload)
    {
        // Call the parent handler first
        $response = parent::handleCustomerSubscriptionCreated($payload);

        // Get the user
        if ($user = $this->getUserByStripeId($payload['data']['object']['customer'])) {
            // Log the event
            Log::info('Subscription created for user: ' . $user->id);

            // Send welcome email
            #Mail::to($user)->send(new SubscriptionCreated($user));

            // Additional business logic (e.g., grant access to features)
            // $user->grantSubscriptionAccess();
        }

        return $response;
    }

    /**
     * Handle subscription cancelled.
     *
     * @param array $payload
     * @return Response
     */
    protected function handleCustomerSubscriptionDeleted(array $payload)
    {
        // Call the parent handler first
        $response = parent::handleCustomerSubscriptionDeleted($payload);

        if ($user = $this->getUserByStripeId($payload['data']['object']['customer'])) {
            // Log the cancellation
            Log::info('Subscription cancelled for user: ' . $user->id);

            // Send cancellation email
            #Mail::to($user)->send(new SubscriptionCancelled($user));

            // Additional business logic (e.g., revoke access)
            // $user->revokeSubscriptionAccess();
        }

        return $response;
    }

    /**
     * Handle payment failed.
     *
     * @param array $payload
     * @return Response
     */
    protected function handleInvoicePaymentFailed(array $payload)
    {
        if ($user = $this->getUserByStripeId($payload['data']['object']['customer'])) {
            // Log the failed payment
            Log::error('Payment failed for user: ' . $user->id);

            // Send payment failed notification
            #Mail::to($user)->send(new PaymentFailed($user));

            // Additional business logic (e.g., restrict access)
            // $user->restrictAccess();
        }

        return $this->successMethod();
    }

    /**
     * Handle subscription updated.
     *
     * @param array $payload
     * @return Response
     */
    protected function handleCustomerSubscriptionUpdated(array $payload)
    {
        // Call the parent handler first
        $response = parent::handleCustomerSubscriptionUpdated($payload);

        if ($user = $this->getUserByStripeId($payload['data']['object']['customer'])) {
            // Log the update
            Log::info('Subscription updated for user: ' . $user->id, [
                'status' => $payload['data']['object']['status'],
                'plan' => $payload['data']['object']['items']['data'][0]['price']['id']
            ]);

            // Handle status changes
            $status = $payload['data']['object']['status'];
            switch ($status) {
                case 'past_due':
                    // Handle past due status
                    break;
                case 'incomplete':
                    // Handle incomplete status
                    break;
                case 'active':
                    // Handle reactivation
                    break;
            }
        }

        return $response;
    }

    protected function handleInvoicePaymentSucceeded(array $payload)
    {
        Log::info('Invoice payment succeeded', [
            'invoice_id' => $payload['data']['object']['id'],
            'customer' => $payload['data']['object']['customer'],
            'amount' => $payload['data']['object']['amount_paid'],
            'subscription' => $payload['data']['object']['subscription']
        ]);

        if ($user = $this->getUserByStripeId($payload['data']['object']['customer'])) {
            Log::info('Found user for payment', ['user_id' => $user->id]);

            // You can add any additional logic here
            // For example, updating user's payment status, sending confirmation emails, etc.
        }

        return $this->successMethod();
    }
}