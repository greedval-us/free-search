<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'name' => [
                ...$this->nameRules(),
                'regex:/^[A-Za-z0-9\\s.\'-]+$/',
            ],
            'email' => [
                ...$this->emailRules(),
                'email:rfc,dns',
            ],
            'password' => [
                'required',
                'string',
                Password::min(10)->mixedCase()->numbers()->symbols(),
                'confirmed',
            ],
            'accept_service_rules' => [
                'accepted',
            ],
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);
    }
}
