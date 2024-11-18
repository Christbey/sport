@php
    use Laravel\Jetstream\TeamInvitation;
    // Check for invitation using the email from the URL parameter instead of old('email')
    $invitation = TeamInvitation::where('email', request('email'))->first();
@endphp

<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo/>
        </x-slot>

        <x-validation-errors class="mb-4"/>

        @if (session('status'))
            <div class="mb-4 font-medium text-sm text-green-600">
                {{ session('status') }}
            </div>
        @endif

        @if ($invitation)
            <!-- Invited User Registration Form -->
            <div class="mb-4 text-sm text-green-600">
                {{ __('You\'ve been invited to join. Please complete your registration.') }}
            </div>

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div>
                    <x-label for="name" value="{{ __('Name') }}"/>
                    <x-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required
                             autofocus autocomplete="name"/>
                </div>

                <div class="mt-4">
                    <x-label for="email" value="{{ __('Email') }}"/>
                    <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="$invitation->email"
                             required readonly/>
                </div>

                <div class="mt-4">
                    <x-label for="password" value="{{ __('Password') }}"/>
                    <x-input id="password" class="block mt-1 w-full" type="password" name="password" required
                             autocomplete="new-password"/>
                </div>

                <div class="mt-4">
                    <x-label for="password_confirmation" value="{{ __('Confirm Password') }}"/>
                    <x-input id="password_confirmation" class="block mt-1 w-full" type="password"
                             name="password_confirmation" required autocomplete="new-password"/>
                </div>

                @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                    <div class="mt-4">
                        <x-label for="terms">
                            <div class="flex items-center">
                                <x-checkbox name="terms" id="terms" required/>
                                <div class="ms-2">
                                    {!! __('I agree to the :terms_of_service and :privacy_policy', [
                                        'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">'.__('Terms of Service').'</a>',
                                        'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">'.__('Privacy Policy').'</a>',
                                    ]) !!}
                                </div>
                            </div>
                        </x-label>
                    </div>
                @endif

                <div class="flex items-center justify-end mt-4">
                    <x-button class="ms-4">
                        {{ __('Register') }}
                    </x-button>
                </div>
            </form>
        @else
            <!-- Access Request Form -->
            <form method="POST" action="{{ route('request-access') }}">
                @csrf
                <div>
                    <x-label for="name" value="{{ __('Name') }}"/>
                    <x-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required
                             autofocus autocomplete="name"/>
                </div>

                <div class="mt-4">
                    <x-label for="email" value="{{ __('Email') }}"/>
                    <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')"
                             required autocomplete="username"/>
                </div>

                <div class="mt-4">
                    <x-label for="reason" value="{{ __('Why would you like to join?') }}"/>
                    <textarea id="reason" name="reason"
                              class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                              required>{{ old('reason') }}</textarea>
                </div>

                <div class="flex items-center justify-end mt-4">
                    <x-button class="ml-4">
                        {{ __('Request Access') }}
                    </x-button>
                </div>
            </form>
        @endif
    </x-authentication-card>
</x-guest-layout>