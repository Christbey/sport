{{-- add-payment-method.blade.php --}}
<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <h1 class="text-2xl font-bold mb-6">Add Payment Method</h1>

                    <div class="space-y-4">
                        {{-- Express Checkout Section --}}
                        <div class="mb-8">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
                                Quick Checkout Options
                            </label>
                            <div id="payment-request-button" class="mb-4">
                                <!-- Apple Pay / Google Pay button will be inserted here -->
                            </div>
                            <div class="relative">
                                <div class="absolute inset-0 flex items-center">
                                    <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
                                </div>
                                <div class="relative flex justify-center text-sm">
                                    <span class="px-2 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400">Or pay with card</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .StripeElement {
            background-color: white;
            padding: 12px;
            border-radius: 6px;
        }

        .dark .StripeElement {
            background-color: rgb(55, 65, 81);
            color: white;
        }

        .StripeElement--focus {
            box-shadow: 0 0 0 2px rgb(59, 130, 246);
        }

        .StripeElement--invalid {
            border-color: rgb(239, 68, 68);
        }
    </style>

    <script src="https://js.stripe.com/v3/"></script>
    <script>
        const stripe = Stripe('{{ config('cashier.key') }}');
        const elements = stripe.elements();

        const clientSecret = "{{ $client_secret }}"; // Inject client secret from the controller

        // Payment Request setup
        const paymentRequest = stripe.paymentRequest({
            country: 'US',
            currency: 'usd',
            total: {
                label: 'Total',
                amount: 5000, // Example amount in cents ($50.00)
            },
            requestPayerName: true,
            requestPayerEmail: true,
        });

        const prButton = elements.create('paymentRequestButton', {
            paymentRequest,
        });

        // Check if the PaymentRequest is available
        paymentRequest.canMakePayment().then(result => {
            if (result) {
                prButton.mount('#payment-request-button');
            } else {
                document.getElementById('payment-request-button').style.display = 'none';
            }
        });

        // Handle the PaymentRequest button submission
        paymentRequest.on('paymentmethod', async (event) => {
            const {error} = await stripe.confirmCardPayment(
                clientSecret, // Use the injected client secret
                {
                    payment_method: event.paymentMethod.id,
                },
                {
                    handleActions: false,
                }
            );

            if (error) {
                event.complete('fail');
                console.error('Payment failed:', error.message);
            } else {
                event.complete('success');
                // Redirect on successful payment
                window.location.href = '{{ route('payment.success') }}';
            }
        });
    </script>
</x-app-layout>

