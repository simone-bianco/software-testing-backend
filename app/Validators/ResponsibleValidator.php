<?php

namespace App\Validators;

use App\Models\Responsible;
use Illuminate\Validation\Rule;

class ResponsibleValidator extends EntityValidator
{
    /**
     * @param $extendParameters
     * @return array
     */
    protected function getRules($extendParameters): array
    {
        /** @var Responsible $responsible */
        $responsible = \Arr::get($extendParameters, 'responsible') ?? Responsible::factory()->newModel();

        return [
            'account_id' => ['required', Rule::unique('patients', 'account_id')->ignore($responsible),
                Rule::exists('accounts', 'id')],
            'structure_id' => ['required', Rule::exists('structures', 'id')],
        ];
    }
}
