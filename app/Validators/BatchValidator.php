<?php

namespace App\Validators;

use App\Models\Batch;
use Illuminate\Validation\Rule;

class BatchValidator extends EntityValidator
{
    /**
     * @param $extendParameters
     * @return array
     */
    protected function getRules($extendParameters): array
    {
        /** @var Batch $batch */
        $batch = \Arr::get($extendParameters, 'batch') ?? Batch::factory()->newModel();

        return [
            'code' => ['required', 'string', Rule::unique('batches', 'code')->ignore($batch), 'min:8', 'max:8'],
            'vaccine_id' => ['required', 'int', Rule::exists('vaccines', 'id')],
        ];
    }
}
