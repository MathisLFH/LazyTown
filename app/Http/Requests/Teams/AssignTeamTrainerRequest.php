<?php

namespace App\Http\Requests\Teams;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTeamTrainerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'trainer_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn (Builder $query) => $query
                    ->whereJsonContains('roles', UserRole::Coach->value)
                    ->where(fn (Builder $tenantQuery) => $tenantQuery
                        ->whereNull('tenant_id')
                        ->orWhere('tenant_id', $this->user()?->tenant_id))),
            ],
        ];
    }
}
