<?php

namespace App\Validators\Controller;

use App\Validators\EntityValidator;
use Illuminate\Validation\Rule;

class ResponsibleCreationValidator extends EntityValidator
{
    /**
     * @param $extendParameters
     * @return array
     */
    protected function getRules($extendParameters): array
    {
        return [
            'first_name' => config('validation.first_name'),
            'last_name' => config('validation.last_name'),
            'email' => ['required',  Rule::unique('users', 'email'), 'email', 'max:255'],
            'date_of_birth' => ['date_format:Y-m-d', 'before:today'],
            'gender' => ['required', 'int', 'in:0,1,2'],
            'fiscal_code' => [
                config('validation.fiscal_code'),
                Rule::unique('accounts', 'fiscal_code')
            ],
            'city' => ['max:30', "alpha"],
            'address' => ['max:255', "alpha"],
            'cap' => config('validation.cap'),
            'mobile_phone' => ['required', 'string', Rule::unique('accounts', 'mobile_phone')],
        ];
    }
}
