<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h2 class="text-2xl font-bold mb-4">
                        @if(isset($plan))
                            Add Payment Method for {{ $plan->name }} Plan
                        @else
                            Complete Your Payment
                        @endif
                    </h2>

                    @if(isset($amount))
                        <p class="text-gray-600 mt-2 mb-4">
                            Amount due: ${{ number_format($amount, 2) }}
                        </p>
                    @endif

                    <!-- Express Checkout Section -->
                    <div id="express-checkout-element" class="mb-6">
                        <!-- Apple Pay / Google Pay will be inserted here -->
                    </div>

                    <form
                            id="payment-form"
                            action="{{ isset($plan) ? route('subscription.add-payment-method') : route('cashier.payment.process') }}"
                            method="POST"
                            class="max-w-md mx-auto"
                    >
                        @csrf

                        @if(isset($plan))
                            <input type="hidden" name="plan_id" value="{{ $planId }}">
                        @endif

                        @if(!isset($plan))
                            <input type="hidden" name="payment_intent" value="{{ request()->route('id') }}">
                            <input type="hidden" name="redirect" value="{{ request('redirect') }}">
                        @endif

                        <input type="hidden" name="payment_method" id="payment-method">

                        <div class="mb-4">
                            <label for="card-holder-name" class="block text-sm font-medium text-gray-700">
                                Cardholder Name
                            </label>
                            <input
                                    id="card-holder-name"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    required
                            >
                        </div>

                        <div class="mb-4">
                            <label for="card-element" class="block text-sm font-medium text-gray-700">
                                Credit or Debit Card
                            </label>
                            <div
                                    id="card-element"
                                    class="mt-1 p-2 border rounded-md"
                            ></div>
                            <div
                                    id="card-errors"
                                    role="alert"
                                    class="mt-2 text-sm text-red-600"
                            ></div>
                        </div>

                        <button
                                type="submit"
                                id="card-button"
                                data-secret="{{ $intent->client_secret }}"
                                class="w-full bg-blue-600 text-white rounded-md px-4 py-2 hover:bg-blue-700"
                        >
                            @if(isset($plan))
                                Add Payment Method
                            @else
                                Pay ${{ isset($amount) ? number_format($amount, 2) : '' }}
                            @endif
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://js.stripe.com/v3/"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const stripe = Stripe('{{ config('services.stripe.key') }}');
                const elements = stripe.elements();

                // Express Checkout setup
                const expressCheckoutOptions = {
                    wallets: {
                        applePay: 'auto',
                        googlePay: 'auto',
                    },
                };

                const expressCheckoutElement = elements.create('expressCheckout', {
                    ...expressCheckoutOptions,
                    amount: {{ isset($amount) ? $amount * 100 : 0 }},
                    currency: '{{ isset($plan) ? $plan->currency : 'usd' }}',
                });

                expressCheckoutElement.mount('#express-checkout-element');

                // Card element setup
                const cardElement = elements.create('card');
                cardElement.mount('#card-element');

                const form = document.getElementById('payment-form');
                const cardButton = document.getElementById('card-button');
                const cardHolderName = document.getElementById('card-holder-name');
                const paymentMethodInput = document.getElementById('payment-method');

                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    cardButton.disabled = true;

                    try {
                        const isAddPaymentMethod = form.action.includes('add-payment-method');

                        if (isAddPaymentMethod) {
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
                                const errorElement = document.getElementById('card-errors');
                                errorElement.textContent = error.message;
                                cardButton.disabled = false;
                            } else {
                                paymentMethodInput.value = setupIntent.payment_method;
                                form.submit();
                            }
                        } else {
                            const {error} = await stripe.confirmCardPayment(
                                cardButton.dataset.secret, {
                                    payment_method: {
                                        card: cardElement,
                                        billing_details: {
                                            name: cardHolderName.value,
                                        },
                                    },
                                    return_url: '{{ route('subscription.manage') }}',
                                }
                            );

                            if (error) {
                                const errorElement = document.getElementById('card-errors');
                                errorElement.textContent = error.message;
                                cardButton.disabled = false;
                            } else {
                                form.submit();
                            }
                        }
                    } catch (err) {
                        console.error('Payment confirmation error:', err);
                        cardButton.disabled = false;
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
