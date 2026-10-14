<?php

namespace App\Http\Requests\Plans;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveTrainingPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->manageableTeamsForPlans()->isNotEmpty() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rows' => ['present', 'array', 'max:200'],
            'rows.*.id' => ['nullable', 'integer'],
            'rows.*.team_id' => ['required', 'integer'],
            'rows.*.gym_name' => ['required', 'string', 'max:255'],
            'rows.*.weekday' => ['required', Rule::in(['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'])],
            'rows.*.starts_at' => ['required', 'date_format:H:i'],
            'rows.*.ends_at' => ['required', 'date_format:H:i'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $manageableTeamIds = $this->user()?->manageableTeamsForPlans()->pluck('id')->all() ?? [];

            foreach ($this->input('rows', []) as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }

                $teamId = (int) ($row['team_id'] ?? 0);

                if (! in_array($teamId, $manageableTeamIds, true)) {
                    $validator->errors()->add("rows.$index.team_id", 'Dieses Team darfst du nicht bearbeiten.');
                }

                $startsAt = $row['starts_at'] ?? null;
                $endsAt = $row['ends_at'] ?? null;

                if (is_string($startsAt) && is_string($endsAt) && $startsAt >= $endsAt) {
                    $validator->errors()->add("rows.$index.ends_at", 'Die Endzeit muss nach der Startzeit liegen.');
                }
            }
        });
    }
}
