<?php
// PaymentMethodController.php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Stripe\SetupIntent;
use Stripe\Stripe;

class PaymentMethodController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('cashier.secret'));
    }

    public function index()
    {
        $user = auth()->user();

        $paymentMethods = $user->paymentMethods();
        $defaultPaymentMethod = $user->defaultPaymentMethod() ?: null;

        return view('payment-methods.index', [
            'paymentMethods' => $paymentMethods,
            'defaultPaymentMethod' => $defaultPaymentMethod
        ]);
    }

    public function setDefault(Request $request, $paymentMethodId)
    {
        try {
            $user = auth()->user();
            $user->updateDefaultPaymentMethod($paymentMethodId);

            return back()->with('success', 'Default payment method updated successfully.');
        } catch (Exception $e) {
            report($e);
            return back()->with('error', 'Unable to update default payment method.');
        }
    }


    public function create()
    {
        $user = auth()->user();

        // Create a SetupIntent
        $setupIntent = $user->createSetupIntent();

        // Pass the client secret to the view
        return view('payment.add-payment-method', [
            'client_secret' => $setupIntent->client_secret,
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|string',
            'set_default' => 'boolean' // optional flag to set as default
        ]);

        try {
            $user = auth()->user();

            // Add the payment method to the customer
            $paymentMethod = $user->addPaymentMethod($request->payment_method);

            // Set as default if requested or if it's the first payment method
            if ($request->boolean('set_default') || !$user->hasDefaultPaymentMethod()) {
                $user->updateDefaultPaymentMethod($paymentMethod->id);
            }

            // Return response based on the request source
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment method added successfully',
                    'is_default' => $request->boolean('set_default')
                ]);
            }

            return redirect()
                ->route('payment.index')
                ->with('success', 'Payment method added successfully.');

        } catch (IncompletePayment $exception) {
            return redirect()->route('cashier.payment', [
                $exception->payment->id,
                'redirect' => route('payment.index')
            ]);
        } catch (Exception $e) {
            report($e);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to add payment method.'
                ], 422);
            }

            return back()->with('error', 'Unable to add payment method.');
        }
    }

    public function remove(Request $request, $paymentMethodId)
    {
        try {
            $user = auth()->user();
            $paymentMethod = $user->findPaymentMethod($paymentMethodId);

            // Don't allow removing the default payment method if it's the only one
            if ($user->hasDefaultPaymentMethod() &&
                $paymentMethod->id === $user->defaultPaymentMethod()->id &&
                count($user->paymentMethods()) === 1) {
                return back()->with('error', 'Cannot remove your only payment method.');
            }

            $paymentMethod->delete();

            return back()->with('success', 'Payment method removed successfully.');
        } catch (Exception $e) {
            report($e);
            return back()->with('error', 'Unable to remove payment method.');
        }
    }

    public function success(Request $request)
    {
        // Handle the return from setup confirmation
        $setupIntentId = $request->query('setup_intent');

        try {
            $user = auth()->user();
            $subscription = $user->subscription('default'); // Fetch the subscription (adjust as needed)

            if ($setupIntentId) {
                $setupIntent = SetupIntent::retrieve($setupIntentId);

                if ($setupIntent->status === 'succeeded') {
                    // Add the payment method
                    $paymentMethod = $user->addPaymentMethod($setupIntent->payment_method);

                    // Set as default if it's the first payment method
                    if (!$user->hasDefaultPaymentMethod()) {
                        $user->updateDefaultPaymentMethod($paymentMethod->id);
                    }

                    return view('payment.success', [
                        'success' => true,
                        'message' => 'Payment method added successfully.',
                        'subscription' => $subscription, // Pass the subscription to the view
                    ]);
                }
            }
        } catch (Exception $e) {
            report($e);
        }

        return view('payment.success', [
            'success' => false,
            'message' => 'There was an error processing your payment method.',
            'subscription' => $subscription ?? null, // Handle cases where subscription is null
        ]);
    }


}