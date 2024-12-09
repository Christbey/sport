<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $stripeSecretKey = config('services.stripe.api.secret');
        $endpointSecret = config('services.stripe.webhook.secret');

        if (!$stripeSecretKey || !$endpointSecret) {
            Log::error('Stripe keys are not configured.');
            return response('Stripe keys are not configured', 500);
        }

        Stripe::setApiKey($stripeSecretKey);

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $event = null;

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (UnexpectedValueException $e) {
            Log::error('Invalid payload:', ['error' => $e->getMessage()]);
            return response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Invalid signature:', [
                'error' => $e->getMessage(),
                'payload' => $payload,
                'sigHeader' => $sigHeader,
            ]);
            return response('Invalid signature', 400);
        }

        Log::info('Webhook event received:', ['type' => $event->type, 'id' => $event->id]);

        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event->data->object);
                break;

            case 'checkout.session.completed':
                $this->handleCheckoutSessionCompleted($event->data->object);
                break;

            default:
                Log::warning('Unhandled event type: ' . $event->type);
        }

        return response('Webhook handled', 200);
    }

    protected function handlePaymentIntentSucceeded($paymentIntent)
    {
        Log::info('PaymentIntent succeeded:', ['payment_intent' => $paymentIntent]);
        // Your business logic here
    }

    protected function handleCheckoutSessionCompleted($session)
    {
        Log::info('Checkout session completed:', ['session' => $session]);
        // Your business logic here
    }
}
