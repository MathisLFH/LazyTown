<?php

namespace App\Http\Requests\Plans;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ParseCsvRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        return $this->routeIs('spielplan.csv')
            ? $user->manageableTeamsForMatchPlans()->isNotEmpty()
            : $user->manageableTeamsForPlans()->isNotEmpty();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ];
    }
}
