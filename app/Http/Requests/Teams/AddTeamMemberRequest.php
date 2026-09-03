<?php

namespace App\Http\Requests\Teams;

use App\Enums\InvitationRole;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddTeamMemberRequest extends FormRequest
{
    public function rules(): array
    {
        abort_if(! $this->route('team') instanceof Team, 404);

        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'role' => ['required', 'string', Rule::enum(InvitationRole::class)],
        ];
    }
}
