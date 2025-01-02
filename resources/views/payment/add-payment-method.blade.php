<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <h1 class="text-2xl font-bold mb-6">Add Payment Method</h1>

                    <!-- Express Checkout Section -->
                    <div id="express-checkout-element" class="mb-6">
                        <!-- Express Checkout Element will be inserted here -->
                    </div>

                    <!-- Payment Form Section -->
                    <form id="payment-form" action="{{ route('payment.store') }}" method="POST">
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
            // Initialize Stripe with the public key from configuration
            const stripe = Stripe('{{ config('cashier.key') }}');

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

            // Options for Express Checkout Element
            const options = {
                wallets: {
                    applePay: 'auto',
                    googlePay: 'auto',
                },
            };

            // Initialize Stripe Elements with custom appearance and mode settings
            const elements = stripe.elements({
                mode: 'payment',
                amount: 1099, // Example amount in cents ($10.99)
                currency: 'usd',
                appearance,
            });

            // Mount Express Checkout Element
            const expressCheckoutElement = elements.create('expressCheckout', options);
            expressCheckoutElement.mount('#express-checkout-element');

            // Mount Payment Element
            const paymentElement = elements.create('payment');
            paymentElement.mount('#payment-element');

            // Handle form submission
            const form = document.getElementById('payment-form');
            const paymentButton = document.getElementById('payment-button');
            const errorContainer = document.getElementById('payment-errors');

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                paymentButton.disabled = true;
                errorContainer.textContent = ''; // Clear previous errors

                try {
                    const {error} = await stripe.confirmSetup({
                        elements,
                        confirmParams: {
                            return_url: '{{ route('payment.success') }}',
                        },
                    });

                    if (error) {
                        errorContainer.textContent = error.message;
                        paymentButton.disabled = false;
                    }
                } catch (error) {
                    errorContainer.textContent = 'An unexpected error occurred. Please try again.';
                    paymentButton.disabled = false;
                    console.error('Stripe error:', error);
                }
            });
        });
    </script>
</x-app-layout>
