<!-- resources/views/subscriptions/success.blade.php -->
<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center">
                    <h2 class="text-2xl font-bold text-green-600">Subscription Successful!</h2>
                    <p class="mt-4 text-gray-600">
                        Thank you for subscribing. Your subscription has been activated.
                    </p>
                    <a href="{{ route('dashboard') }}"
                       class="mt-6 inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        Go to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>