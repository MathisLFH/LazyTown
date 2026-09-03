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

        if (! $this->user()->hasRole('trainer')) {
            $roles = array_values(array_filter($roles, fn (mixed $role): bool => $role !== 'trainer'));
        }

        if ($roles === []) {
            $roles = ['spieler'];
        }

        $this->merge([
            'roles' => $roles,
            'active_role' => $this->input('active_role', $this->user()->active_role ?: $roles[0]),
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
