<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">Add Payment Method</h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Before HELLLO changing to the {{ $plan->name }} plan, please add a payment method.
                    </p>

                    <!-- Express Checkout Section -->
                    <div id="express-checkout-element" class="mb-6">
                        <!-- Express Checkout Element (Apple Pay, Google Pay, etc.) will be inserted here -->
                    </div>

                    <!-- Payment Form Section -->
                    <form id="payment-form" action="{{ route('subscription.add-payment-method') }}" method="POST">
                        @csrf
                        <div id="payment-element" class="mb-6">
                            <!-- Stripe Payment Element will be inserted here -->
                        </div>
                        <div id="payment-errors" class="text-sm text-red-600 mb-4"></div>
                        <button
                                type="submit"
                                id="payment-button"
                                class="w-full bg-blue-600 text-white rounded-md px-4 py-2 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
                        >
                            Add Payment Method
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://js.stripe.com/v3/"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const stripeKey = '{{ config('services.stripe.api.key') }}';

            if (!stripeKey) {
                console.error('Stripe publishable key is missing');
                document.getElementById('card-errors').textContent = 'Configuration error. Please contact support.';
                document.getElementById('card-button').disabled = true;
                return;
            }

            const stripe = Stripe(stripeKey);

            // Appearance customization for Stripe Elements
            const appearance = {
                theme: 'flat',
                variables: {
                    colorPrimary: '#0570de',
                    colorBackground: '#ffffff',
                    colorText: '#333333',
                    colorDanger: '#df1b41',
                    fontFamily: 'Arial, sans-serif',
                    spacingUnit: '4px',
                    borderRadius: '4px',
                },
            };

            // Setup Express Checkout
            const expressCheckoutOptions = {
                wallets: {
                    applePay: 'auto',
                    googlePay: 'auto',
                },
            };

            const elements = stripe.elements({appearance});

            const expressCheckoutElement = elements.create('expressCheckout', {
                ...expressCheckoutOptions,
                amount: {{ $plan->price * 100 }}, // Convert plan price to cents
                currency: '{{ $plan->currency }}', // Ensure your plan has a `currency` attribute
            });
            expressCheckoutElement.mount('#express-checkout-element');

            // Setup Card Element
            const cardElement = elements.create('card');
            cardElement.mount('#card-element');

            const form = document.getElementById('payment-form');
            const cardButton = document.getElementById('card-button');
            const cardHolderName = document.getElementById('card-holder-name');
            const errorElement = document.getElementById('card-errors');

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                cardButton.disabled = true;
                errorElement.textContent = ''; // Clear previous errors

                try {
                    const {setupIntent, error} = await stripe.confirmCardSetup(
                        cardButton.dataset.secret, {
                            payment_method: {
                                card: cardElement,
                                billing_details: {
                                    name: cardHolderName.value,
                                },
                            },
                        }
                    );

                    if (error) {
                        errorElement.textContent = error.message;
                        cardButton.disabled = false;
                    } else {
                        document.getElementById('payment-method').value = setupIntent.payment_method;
                        form.submit();
                    }
                } catch (e) {
                    errorElement.textContent = 'An unexpected error occurred. Please try again.';
                    cardButton.disabled = false;
                    console.error('Stripe error:', e);
                }
            });
        });
    </script>
</x-app-layout>
