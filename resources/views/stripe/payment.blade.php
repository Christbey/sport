<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-bold">Complete Your Payment</h2>
                        <p class="text-gray-600 mt-2">Amount due: ${{ number_format($amount, 2) }}</p>
                    </div>

                    <form id="payment-form" action="{{ route('cashier.payment.process') }}" method="POST"
                          class="max-w-md mx-auto">
                        @csrf
                        <input type="hidden" name="payment_intent" value="{{ request()->route('id') }}">
                        <input type="hidden" name="redirect" value="{{ request('redirect') }}">

                        <div class="mb-6">
                            <label for="card-holder-name" class="block text-sm font-medium text-gray-700">
                                Cardholder Name
                            </label>
                            <input type="text" id="card-holder-name"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   required>
                        </div>

                        <div class="mb-6">
                            <label for="card-element" class="block text-sm font-medium text-gray-700">
                                Credit or debit card
                            </label>
                            <div id="card-element"
                                 class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2">
                                <!-- Stripe Element will be inserted here -->
                            </div>
                            <div id="card-errors" role="alert" class="mt-2 text-sm text-red-600"></div>
                        </div>

                        <button type="submit" id="card-button"
                                data-secret="{{ $intent->client_secret }}"
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Pay ${{ number_format($amount, 2) }}
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
                const stripe = Stripe('{{ $stripeKey }}');
                const elements = stripe.elements();
                const cardElement = elements.create('card');

                cardElement.mount('#card-element');

                const form = document.getElementById('payment-form');
                const cardButton = document.getElementById('card-button');
                const cardHolderName = document.getElementById('card-holder-name');

                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    cardButton.disabled = true;

                    try {
                        const {error, paymentIntent} = await stripe.confirmCardPayment(
                            cardButton.dataset.secret,
                            {
                                payment_method: {
                                    card: cardElement,
                                    billing_details: {
                                        name: cardHolderName.value
                                    }
                                },
                                return_url: '{{ route('subscription.manage') }}'
                            }
                        );

                        if (error) {
                            const errorElement = document.getElementById('card-errors');
                            errorElement.textContent = error.message;
                            cardButton.disabled = false;
                        } else {
                            form.submit();
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