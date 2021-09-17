<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Patient::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'heart_disease' => !rand(0, 1),
            'allergy' => !rand(0, 1),
            'immunosuppression' => !rand(0, 1),
            'anticoagulants' => !rand(0, 1),
            'covid' => !rand(0, 1),
        ];
    }
}
