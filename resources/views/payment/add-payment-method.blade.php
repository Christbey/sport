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
                            <div id="express-checkout-element" class="mb-4">
                                <!-- Express Checkout Element will be inserted here -->
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

                        {{-- Card Details Section --}}
                        <div>
                            <label for="card-holder-name"
                                   class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Card Holder Name
                            </label>
                            <input type="text" id="card-holder-name"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="card-element"
                                   class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Credit or Debit Card
                            </label>
                            <div id="card-element"
                                 class="mt-1 p-3 border rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700"></div>
                            <div id="card-errors" role="alert"
                                 class="mt-2 text-red-600 dark:text-red-400 text-sm"></div>
                        </div>

                        <div class="flex items-center justify-between mt-6">
                            <button id="card-button" data-secret="{{ $intent->client_secret }}"
                                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">
                                Add Payment Method
                            </button>
                            <a href="{{ route('payment.methods') }}"
                               class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                                Cancel
                            </a>
                        </div>

                        <form id="payment-form" action="{{ route('payment.store') }}" method="POST" class="hidden">
                            @csrf
                            <input type="hidden" name="payment_method" id="payment-method-input">
                        </form>
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

        // Initialize Elements with Express Checkout options
        const elements = stripe.elements({
            mode: 'setup',
            currency: 'usd',
            returnUrl: '{{ route('payment.success') }}'
        });

        // Create and mount Express Checkout Element
        const expressCheckoutElement = elements.create('expressCheckout');
        expressCheckoutElement.mount('#express-checkout-element');

        // Handle Express Checkout click
        expressCheckoutElement.on('click', (event) => {
            const options = {
                emailRequired: true
            };
            event.resolve(options);
        });

        // Handle Express Checkout confirmation
        expressCheckoutElement.on('confirm', async (event) => {
            const {error} = await stripe.confirmSetup({
                elements,
                confirmParams: {
                    return_url: '{{ route('payment.success') }}',
                },
                redirect: 'if_required',
            });

            if (error) {
                const errorElement = document.getElementById('card-errors');
                errorElement.textContent = error.message;
            }
        });

        // Listen for the ready event
        expressCheckoutElement.on('ready', (event) => {
            // Express Checkout is ready for customer interaction
            console.log('Express Checkout is ready');
        });

        // Create and mount regular card Element
        const cardElement = elements.create('card', {
            style: {
                base: {
                    color: document.documentElement.classList.contains('dark') ? '#fff' : '#32325d',
                    fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                    fontSmoothing: 'antialiased',
                    fontSize: '16px',
                    '::placeholder': {
                        color: document.documentElement.classList.contains('dark') ? '#6b7280' : '#aab7c4'
                    }
                }
            }
        });
        cardElement.mount('#card-element');

        // Regular card form handling
        const cardHolderName = document.getElementById('card-holder-name');
        const cardButton = document.getElementById('card-button');
        const cardErrors = document.getElementById('card-errors');
        const form = document.getElementById('payment-form');
        const paymentMethodInput = document.getElementById('payment-method-input');
        const clientSecret = cardButton.dataset.secret;

        cardButton.addEventListener('click', async (e) => {
            e.preventDefault();
            cardButton.disabled = true;
            cardButton.textContent = 'Processing...';

            try {
                const {setupIntent, error} = await stripe.confirmCardSetup(
                    clientSecret,
                    {
                        payment_method: {
                            card: cardElement,
                            billing_details: {name: cardHolderName.value}
                        }
                    }
                );

                if (error) {
                    cardErrors.textContent = error.message;
                    cardButton.disabled = false;
                    cardButton.textContent = 'Add Payment Method';
                } else {
                    paymentMethodInput.value = setupIntent.payment_method;
                    form.submit();
                }
            } catch (e) {
                cardErrors.textContent = 'An unexpected error occurred.';
                cardButton.disabled = false;
                cardButton.textContent = 'Add Payment Method';
            }
        });

        // Handle real-time validation errors
        cardElement.addEventListener('change', ({error}) => {
            if (error) {
                cardErrors.textContent = error.message;
            } else {
                cardErrors.textContent = '';
            }
        });
    </script>
</x-app-layout>

