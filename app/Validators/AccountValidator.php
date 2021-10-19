<?php

namespace App\Validators;

use App\Models\Account;
use Arr;
use Illuminate\Validation\Rule;


class AccountValidator extends EntityValidator
{
    /**
     * @param $extendParameters
     * @return array
     */
    protected function getRules($extendParameters): array
    {
        /** @var Account $account */
        $account = Arr::get($extendParameters, 'account') ?? Account::factory()->newModel();

        return [
            'first_name' => ['max:30', config('validation.first_name')],
            'last_name' => ['max:30', config('validation.last_name')],
            'date_of_birth' => ['date_format:Y-m-d', 'before:today'],
            'gender' => ['required', 'int', 'in:0,1,2'],
            'fiscal_code' => [
                'min:16',
                'max:16',
                config('validation.fiscal_code')
            ],
            'city' => ['max:30', "alpha"],
            'address' => ['max:255', 'required', "string"],
            'cap' => ['min:5', 'max:5', config('validation.cap')],
            'mobile_phone' => ['required', 'string', 'max:32', Rule::unique('accounts', 'mobile_phone')->ignoreModel($account)],
            'user_id' => ['required', Rule::unique('accounts', 'user_id')->ignore($account),
                Rule::exists('users', 'id')],
        ];
    }
}
