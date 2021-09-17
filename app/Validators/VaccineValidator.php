<?php

namespace App\Validators;

use App\Models\Vaccine;

class VaccineValidator extends EntityValidator
{
    /**
     * @param $extendParameters
     * @return array
     */
    protected function getRules($extendParameters): array
    {
        /** @var Vaccine $vaccine */
        $vaccine = \Arr::get($extendParameters, 'vaccine') ?? Vaccine::factory()->newModel();

        return [
            'name' => ['required', config('validation.vaccine_name')],
            'vaccine_doses' => ['required', 'int', 'min:1'],
        ];
    }
}
