<?php

namespace App\Helper\Generator;

use Arr;
use Exception;
use Faker\Generator;

/**
 * Class PatientGenerator
 * @package App\Helper\Generator
 */
class PatientGenerator
{
    protected Generator $faker;

    /**
     * AccountGenerator constructor.
     * @param  Generator  $faker
     */
    public function __construct(
        Generator $faker
    ) {
        $this->faker = $faker;
    }

    /**
     * @param  array  $overwrite
     * @return array
     * @throws Exception
     */
    public function generatePatientAttributes(array $overwrite = []): array
    {
        return [
            'heart_disease' => Arr::get($overwrite, 'heart_disease') ?? (boolean) random_int(0, 1),
            'allergy' => Arr::get($overwrite, 'allergy') ?? (boolean) random_int(0, 1),
            'immunosuppression' => Arr::get($overwrite, 'immunosuppression') ?? (boolean) random_int(0, 1),
            'anticoagulants' => Arr::get($overwrite, 'anticoagulants') ?? (boolean) random_int(0, 1),
            'covid' => Arr::get($overwrite, 'covid') ?? (boolean) random_int(0, 1),
            'account_id' => Arr::get($overwrite, 'account_id'),
        ];
    }
}
