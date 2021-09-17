<?php

namespace App\Validators;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Validation\ValidationException;

abstract class EntityValidator
{
    /**
     * @param $data
     * @param  array  $extendParameters
     * @return array
     * @throws ValidationException
     */
    public function validateData($data, array $extendParameters = []): array
    {
        return $this->getValidationFactory()->make(
            $data, $this->getRules($extendParameters)
        )->validateWithBag('validation');
    }

    abstract protected function getRules($extendParameters);

    /**
     * Get a validation factory instance.
     *
     * @return Factory
     */
    protected function getValidationFactory(): Factory
    {
        return app(Factory::class);
    }
}
