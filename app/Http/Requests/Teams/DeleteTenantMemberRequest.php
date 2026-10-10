<?php

namespace App\Http\Requests\Teams;

use App\Models\Tenant;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DeleteTenantMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $administrator = $this->user();
        $member = $this->route('user');

        if (! $administrator instanceof User || ! $member instanceof User || ! $administrator->tenant instanceof Tenant) {
            return false;
        }

        abort_unless($member->tenant_id === $administrator->tenant_id, 404);
        abort_if(
            $member->is($administrator)
                || $administrator->tenant->admin_id === $member->id
                || $member->ownedTeams()->where('is_personal', false)->exists(),
            403,
        );

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
            'name' => ['required', 'string'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $member = $this->route('user');

                if ($this->has('name') && $member instanceof User && $this->input('name') !== $member->name) {
                    $validator->errors()->add('name', 'Der Name stimmt nicht überein.');
                }
            },
        ];
    }
}
