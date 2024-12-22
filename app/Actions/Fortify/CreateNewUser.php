<?php

namespace App\Actions\Fortify;

use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users',
                //                function ($attribute, $value, $fail) {
                //                    $invitation = TeamInvitation::where('email', $value)->first();
                //                    if (!$invitation) {
                //                        $fail('Registration is only available with a valid team invitation.');
                //                    }
                //                }
            ],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
            ]);

            // Create Stripe Customer
            $user->createOrGetStripeCustomer();

            // Subscribe the user to the free plan
            $user->newSubscription('default', 'price_1QYHzyELTH1Vz3ILkF46LZkA') // Replace with your actual Price ID
            ->create();

            // Handle team invitation
            $invitation = TeamInvitation::where('email', $input['email'])->first();

            if ($invitation) {
                $invitation->team->users()->attach(
                    $user, ['role' => 'viewer']
                );
                $user->switchTeam($invitation->team);
                $invitation->delete();
            }

            return $user;
        });
    }
}