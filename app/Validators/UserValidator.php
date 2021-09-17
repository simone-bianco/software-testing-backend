<?php

namespace App\Validators;

use App\Models\User;
use Illuminate\Validation\Rule;

class UserValidator extends EntityValidator
{
    /**
     * @param $extendParameters
     * @return array
     */
    protected function getRules($extendParameters): array
    {
        /** @var User $user */
        $user = \Arr::get($extendParameters, 'user') ?? User::factory()->newModel();

        return [
            'name' => ['max:60', 'required', config('validation.name')],
            //Versione finale produzione
//            'password' => $user->id ? [] : ['required', config('validation.password')],
            'password' => $user->id ? [] : ['required', 'string'],
            'email' => ['required',  Rule::unique('users', 'email')->ignore($user), 'email', 'max:255'],
        ];
    }
}
