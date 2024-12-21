<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Laravel\Cashier\Payment;
use Log;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class PaymentController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('cashier.secret'));
    }

    public function show(Request $request, $id)
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($id);

            switch ($paymentIntent->status) {
                case 'requires_payment_method':
                    return view('stripe.payment', [
                        'intent' => $paymentIntent,
                        'clientSecret' => $paymentIntent->client_secret,
                        'amount' => $paymentIntent->amount / 100,
                        'stripeKey' => config('services.stripe.key')
                    ]);

                case 'requires_confirmation':
                    // Attempt to confirm the payment
                    $confirmedIntent = PaymentIntent::retrieve($id);
                    $confirmedIntent->confirm();

                    return redirect($request->query('redirect', route('subscription.manage'))
                        ->with('success', 'Payment processed successfully.'));

                case 'succeeded':
                    return redirect($request->query('redirect', route('subscription.manage'))
                        ->with('success', 'Payment processed successfully.'));

                default:
                    return redirect()->route('subscription.manage')
                        ->with('warning', 'Payment status: ' . $paymentIntent->status);
            }
        } catch (Exception $e) {
            report($e);
            return redirect()->route('subscription.manage')
                ->with('error', 'Unable to process payment: ' . $e->getMessage());
        }
    }

    public function processPayment(Request $request)
    {
        try {
            $payment = new Payment(
                $request->user(),
                PaymentIntent::retrieve($request->payment_intent)
            );

            $payment->validate();

            return redirect($request->redirect ?? route('subscription.manage'))
                ->with('success', 'Payment processed successfully.');

        } catch (Exception $e) {
            report($e);
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }


    public function success()
    {
        return view('payment.success');
    }

    public function failure()
    {
        return view('payment.failure');
    }

    /**
     * Add a new payment method
     */
    public function addPaymentMethod(Request $request)
    {
        try {
            $user = $request->user();
            $paymentMethod = $request->input('payment_method');

            $user->addPaymentMethod($paymentMethod);

            return redirect()->route('payment.methods')
                ->with('success', 'Payment method added successfully.');
        } catch (Exception $e) {
            report($e);
            return back()->with('error', 'Unable to add payment method: ' . $e->getMessage());
        }
    }

    /**
     * Set a payment method as default
     */
    public function setDefaultPaymentMethod($paymentMethodId)
    {
        $user = auth()->user();

        try {
            $user->updateDefaultPaymentMethod($paymentMethodId);

            return redirect()->route('payment.methods')
                ->with('success', 'Default payment method updated successfully.');
        } catch (Exception $e) {
            return redirect()->route('payment.methods')
                ->with('error', 'Unable to set default payment method: ' . $e->getMessage());
        }
    }

    /**
     * Remove a payment method
     */
    public function removePaymentMethod(Request $request, $paymentMethodId)
    {
        try {
            $user = $request->user();
            $paymentMethod = $user->findPaymentMethod($paymentMethodId);

            if ($paymentMethod) {
                // Prevent removing the last payment method
//                if ($user->paymentMethods()->count() <= 1) {
//                    return back()->with('error', 'You must have at least one payment method.');
//                }

                $paymentMethod->delete();

                return redirect()->route('payment.methods')
                    ->with('success', 'Payment method removed successfully.');
            }

            return back()->with('error', 'Payment method not found.');
        } catch (Exception $e) {
            report($e);
            return back()->with('error', 'Unable to remove payment method: ' . $e->getMessage());
        }
    }

    public function paymentMethods(Request $request)
    {
        try {
            $user = $request->user();
            $paymentMethods = $user->paymentMethods()->map(function ($method) {
                try {
                    // Safely retrieve card details
                    $card = $method->card ?? null;

                    return (object)[
                        'id' => $method->id,
                        'brand' => $card ? strtoupper($card->brand) : 'Unknown Card',
                        'last4' => $card ? $card->last4 : '****',
                        'exp_month' => $card ? sprintf('%02d', $card->exp_month) : '00',
                        'exp_year' => $card ? $card->exp_year : '0000',
                    ];
                } catch (Exception $e) {
                    Log::error('Error processing payment method', [
                        'method_id' => $method->id,
                        'error' => $e->getMessage()
                    ]);

                    return (object)[
                        'id' => $method->id,
                        'brand' => 'Unknown',
                        'last4' => '****',
                        'exp_month' => '00',
                        'exp_year' => '0000',
                    ];
                }
            });

            $defaultPaymentMethod = $user->defaultPaymentMethod();
            $formattedDefaultMethod = $defaultPaymentMethod ? (object)[
                'id' => $defaultPaymentMethod->id,
                'brand' => $defaultPaymentMethod->card ? strtoupper($defaultPaymentMethod->card->brand) : 'Unknown',
                'last4' => $defaultPaymentMethod->card ? $defaultPaymentMethod->card->last4 : '****',
            ] : null;

            return view('payment.methods', [
                'paymentMethods' => $paymentMethods,
                'defaultPaymentMethod' => $formattedDefaultMethod
            ]);
        } catch (Exception $e) {
            Log::error('Payment methods retrieval error', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('subscription.manage')
                ->with('error', 'Unable to retrieve payment methods. Please try again.');
        }
    }

}