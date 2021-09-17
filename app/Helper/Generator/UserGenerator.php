<?php

namespace App\Helper\Generator;

use Arr;
use Exception;
use Faker\Generator;
use Faker\Provider\it_IT\Person;

/**
 * Class UserGenerator
 * @package App\Helper\Generator
 */
class UserGenerator
{
    /** @var string[] $validCities */
    const VALID_CITIES = ["napoli", "milano", "roma", "torino", "trieste"];

    protected Generator $faker;
    protected EmailGenerator $emailGenerator;

    /**
     * AccountGenerator constructor.
     * @param  Generator  $faker
     * @param  EmailGenerator  $emailGenerator
     */
    public function __construct(
        Generator $faker,
        EmailGenerator $emailGenerator
    ) {
        $this->faker = $faker;
        $this->emailGenerator = $emailGenerator;
    }

    /**
     * @param  array  $overwrite
     * @return array
     * @throws Exception
     */
    public function generateUserAttributes(array $overwrite = []): array
    {
        $this->faker->addProvider(new Person($this->faker));

        $genderValue = Arr::get($overwrite, 'gender') ?? random_int(0, 1);

        $name = Arr::get($overwrite, 'name');
        if (!$name) {
            $firstName = Arr::get($overwrite, 'first_name')
                ?? $this->faker->firstName($genderValue ? 'male' : 'female');
            $lastName = Arr::get($overwrite, 'last_name') ?? $this->faker->lastName;
            $name = $firstName . ' ' . $lastName;
        }

        return [
            'name' => $name,
            'password' => Arr::get($overwrite, 'password') ?? 'password123',
            'email' => $this->emailGenerator->generateEmail($name, '')
        ];
    }
}
