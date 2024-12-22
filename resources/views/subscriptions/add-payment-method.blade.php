{{--@php--}}
{{--    dd(config('services.stripe.api.key'));--}}
{{--@endphp--}}
<x-app-layout>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h2 class="text-2xl font-bold mb-4">Add Payment Method</h2>
                    <p class="mb-4">Before changing to the {{ $plan->name }} plan, please add a payment method.</p>

                    <form id="payment-form" action="{{ route('subscription.add-payment-method') }}" method="POST">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $planId }}">
                        <input type="hidden" name="payment_method" id="payment-method">

                        <div class="mb-4">
                            <label for="card-holder-name" class="block text-sm font-medium text-gray-700">
                                Card Holder Name
                            </label>
                            <input id="card-holder-name" type="text"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                   required>
                        </div>

                        <div class="mb-4">
                            <label for="card-element" class="block text-sm font-medium text-gray-700">
                                Credit or debit card
                            </label>
                            <div id="card-element" class="mt-1 p-2 border rounded-md">
                                <!-- Stripe Elements will insert the card element here -->
                            </div>
                            <div id="card-errors" role="alert" class="mt-2 text-sm text-red-600"></div>
                        </div>

                        <button type="submit"
                                id="card-button"
                                data-secret="{{ $intent->client_secret }}"
                                class="w-full bg-blue-600 text-white rounded-md px-4 py-2 hover:bg-blue-700">
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
            const elements = stripe.elements();
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
                                    name: cardHolderName.value
                                }
                            }
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