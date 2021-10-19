<?php

namespace App\Validators\Controller;

use App\Validators\EntityValidator;
use Illuminate\Validation\Rule;

class RegistrationValidator extends EntityValidator
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
            'password' => ['required', 'string'],
            'email' => ['required',  Rule::unique('users', 'email'), 'email', 'max:255'],
            'date_of_birth' => ['date_format:Y-m-d', 'before:today'],
            'gender' => ['required', 'int', 'in:0,1,2'],
            'fiscal_code' => [
                config('validation.fiscal_code')
            ],
            'city' => ['max:30', "alpha"],
            'cap' => config('validation.cap'),
            'mobile_phone' => ['required', 'string', Rule::unique('accounts', 'mobile_phone')],
            'user_id' => ['required', Rule::unique('accounts', 'user_id')],
            'heart_disease' => ['required', 'bool'],
            'allergy' => ['required', 'bool'],
            'immunosuppression' => ['required', 'bool'],
            'anticoagulants' => ['required', 'bool'],
            'covid' => ['required', 'bool'],
            'account_id' => ['required', Rule::unique('patients', 'account_id')],
        ];
    }
}
