<?php

namespace App\Http\Requests\Teams;

use App\Rules\TeamName;
use Illuminate\Foundation\Http\FormRequest;

class CreateClubRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', new TeamName],
        ];
    }
}
