<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
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
            ...$this->adminInfoRules(),
            'password' => $this->passwordRules(),
            'is_authorized_admin' => ['accepted'],
        ], [
            'is_authorized_admin.accepted' => 'Confirm that you are an authorized barangay health administrator.',
        ])->validate();

        return $this->store($input, approved: false);
    }

    /**
     * Super admin fills in the form for a nurse who registers in person.
     *
     * @param  array<string, string>  $input
     */
    public function createWalkIn(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            ...$this->adminInfoRules(),
            'password' => $this->passwordRules(),
            'identity_checked' => ['accepted'],
        ], [
            'identity_checked.accepted' => 'Confirm that you checked the nurse in person.',
        ])->validate();

        return $this->store($input, approved: true);
    }

    /**
     * @param  array<string, string>  $input
     */
    private function store(array $input, bool $approved): User
    {
        if (User::barangayAdminSlotTaken((string) ($input['barangay'] ?? ''), $input['city'] ?? null)) {
            throw ValidationException::withMessages([
                'barangay' => 'This barangay already has an active or pending administrator.',
            ]);
        }

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'role' => User::ROLE_BARANGAY_ADMIN,
            'is_active' => true,
            'is_approved' => $approved,
            'registered_via' => $approved ? 'walk_in' : 'online',
            'phone' => $input['phone'],
            'position' => $input['position'],
            'region' => $input['region'],
            'province' => $input['province'],
            'city' => $input['city'],
            'barangay' => $input['barangay'],
            'street' => $input['street'],
            'postal_code' => $input['postal_code'] ?? null,
        ]);
    }
}
