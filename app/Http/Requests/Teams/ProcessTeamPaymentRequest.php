<?php

namespace App\Http\Requests\Teams;

use Illuminate\Foundation\Http\FormRequest;

class ProcessTeamPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'cardholder' => ['required', 'string', 'max:255'],
            'card_number' => ['required', 'digits_between:13,19'],
            'expiry' => ['required', 'date_format:m/y'],
            'cvc' => ['required', 'digits_between:3,4'],
        ];
    }
}
