<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    protected function prepareForValidation(): void
    {
        $roles = $this->input('roles', $this->user()->roles ?: ['spieler']);

        if (! is_array($roles)) {
            $roles = [];
        }

        if ($roles === []) {
            $roles = ['spieler'];
        }

        $activeRole = $this->input('active_role');

        if (! is_string($activeRole) || ! in_array($activeRole, $roles, true)) {
            $activeRole = $roles[0];
        }

        $this->merge([
            'roles' => $roles,
            'active_role' => $activeRole,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules($this->user()->id),
            'active_role' => ['required', 'string', Rule::in($this->input('roles', []))],
        ];
    }
}
