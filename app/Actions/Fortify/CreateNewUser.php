<?php

namespace App\Actions\Fortify;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Create a newly registered user.
     *
     * @param array<string, string> $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        return DB::transaction(function () use ($input) {
            return tap(User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
            ]), function (User $user) {
                $this->addToGeneralTeam($user); // Add the user to the General team
            });
        });
    }

    /**
     * Add the user to the General team instead of creating a personal team.
     */
    protected function addToGeneralTeam(User $user): void
    {
        // Fetch the general team by name or another unique identifier
        $generalTeam = Team::where('name', 'General')->first(); // Change 'General' to the actual team name or identifier

        // If the general team exists, attach the user to it
        if ($generalTeam) {
            $user->teams()->attach($generalTeam->id, ['role' => 'viewer']); // Attach the user as a 'member' (or any other role)
            $user->switchTeam($generalTeam); // Optionally set the General team as the current team
        } else {
            // Optionally handle the case when the General team does not exist
            throw new \Exception('General team not found');
        }
    }
}
