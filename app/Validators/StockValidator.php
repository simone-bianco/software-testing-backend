<?php

namespace App\Validators;

use App\Models\Stock;
use Illuminate\Validation\Rule;

class StockValidator extends EntityValidator
{
    /**
     * @param $extendParameters
     * @return array
     */
    protected function getRules($extendParameters): array
    {
        /** @var Stock $stock */
        $stock = \Arr::get($extendParameters, 'stock') ?? Stock::factory()->newModel();

        return [
            'quantity' => ['required', 'int', 'min:0'],
            'code' => ['required', 'string', config('validation.reservation_code'),
                Rule::unique('reservations', 'code')->ignoreModel($stock)],
        ];
    }
}
