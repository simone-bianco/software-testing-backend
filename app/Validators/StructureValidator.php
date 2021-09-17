<?php

namespace App\Validators;

use App\Models\Structure;
use App\Rules\StructureCapacityMultipleOfN;
use Illuminate\Validation\Rule;

class StructureValidator extends EntityValidator
{
    /**
     * @param $extendParameters
     * @return array
     */
    protected function getRules($extendParameters): array
    {
        /** @var Structure $structure */
        $structure = \Arr::get($extendParameters, 'structure') ?? Structure::factory()->newModel();

        return [
            'name' => ['required', 'string', Rule::unique('structures', 'name')->ignoreModel($structure)],
            'region' => ['required', 'string'],
            'phone_number' => ['required', 'string'],
            'capacity' => ['required', 'int', new StructureCapacityMultipleOfN(24)],
        ];
    }
}
