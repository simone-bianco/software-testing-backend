<?php

namespace App\Validators;

use App\Models\Patient;
use Illuminate\Validation\Rule;

class PatientValidator extends EntityValidator
{
    /**
     * @param $extendParameters
     * @return array
     */
    protected function getRules($extendParameters): array
    {
        /** @var Patient $patient */
        $patient = \Arr::get($extendParameters, 'patient') ?? Patient::factory()->newModel();

        return [
            'heart_disease' => ['required', 'bool'],
            'allergy' => ['required', 'bool'],
            'immunosuppression' => ['required', 'bool'],
            'anticoagulants' => ['required', 'bool'],
            'covid' => ['required', 'bool'],
            'account_id' => ['required', Rule::unique('patients', 'account_id')->ignore($patient),
                Rule::exists('accounts', 'id')],
        ];
    }
}
