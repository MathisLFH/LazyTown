<?php

namespace App\Http\Requests\Plans;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveMatchPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $teamId = $this->integer('team_id');

        if ($teamId <= 0) {
            return false;
        }

        return $this->user()?->manageableTeamsForMatchPlans()->contains('id', $teamId) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'team_id' => ['required', 'integer'],
            'season' => ['required', 'regex:/^\d{4}\/\d{2,4}$/'],
            'rows' => ['present', 'array', 'max:200'],
            'rows.*.id' => ['nullable', 'integer'],
            'rows.*.date' => ['required', 'date_format:Y-m-d'],
            'rows.*.versus' => ['required', 'string', 'max:255'],
            'rows.*.at_home' => ['required', 'boolean'],
            'rows.*.city' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $rows = $this->input('rows', []);

            if (! is_array($rows)) {
                return;
            }

            $seen = [];

            foreach ($rows as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }

                $date = (string) ($row['date'] ?? '');
                $versus = mb_strtolower(trim((string) ($row['versus'] ?? '')));
                $atHome = filter_var($row['at_home'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                if ($date === '' || $versus === '' || $atHome === null) {
                    continue;
                }

                $fingerprint = $date.'|'.$versus.'|'.($atHome ? '1' : '0');

                if (isset($seen[$fingerprint])) {
                    $validator->errors()->add("rows.$index.date", 'Diese Spielzeile ist doppelt vorhanden.');
                } else {
                    $seen[$fingerprint] = true;
                }
            }
        });
    }
}
