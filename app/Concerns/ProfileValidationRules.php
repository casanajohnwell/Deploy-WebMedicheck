<?php

namespace App\Concerns;

use App\Models\User;
use App\Support\AdminPositions;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
        ];
    }

    /**
     * Confidential administrator details. Encrypted at rest.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function adminInfoRules(bool $required = true): array
    {
        $presence = $required ? 'required' : 'nullable';

        return [
            'phone' => [$presence, 'string', 'max:30'],
            'position' => [$presence, 'string', 'max:255', Rule::in(AdminPositions::all())],
            'region' => [$presence, 'string', 'max:255'],
            'province' => [$presence, 'string', 'max:255'],
            'city' => [$presence, 'string', 'max:255'],
            'barangay' => [$presence, 'string', 'max:255'],
            'street' => [$presence, 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * Location and contact details for medicine seekers (mobile API).
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function seekerInfoRules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:30'],
            'region' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'street' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }
}
