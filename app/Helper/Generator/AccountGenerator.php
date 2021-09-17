<?php

namespace App\Helper\Generator;

use Arr;
use Carbon\Carbon;
use Exception;
use Faker\Generator;
use Faker\Provider\it_IT\Address;
use Faker\Provider\it_IT\Person;
use Faker\Provider\it_IT\PhoneNumber;
use robertogallea\LaravelCodiceFiscale\CodiceFiscale;

class AccountGenerator
{
    /** @var string[] $validCities */
    const VALID_CITIES = ["napoli", "milano", "roma", "torino", "trieste"];

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
    public function generateAccountAttributes(array $overwrite = []): array
    {
        $this->faker->addProvider(new Person($this->faker));
        $this->faker->addProvider(new Address($this->faker));
        $this->faker->addProvider(new PhoneNumber($this->faker));
        $this->faker->addProvider(new Address($this->faker));

        $genderValue = Arr::get($overwrite, 'gender') ?? random_int(0, 1);
        $firstName = Arr::get($overwrite, 'first_name') ?? $this->faker->firstName($genderValue ? 'male' : 'female');
        $lastName = Arr::get($overwrite, 'last_name') ?? $this->faker->lastName;
        $dob = Arr::get($overwrite, 'date_of_birth') ?? Carbon::today()
            ->subYears(random_int(15, 90))
            ->subMonths(random_int(0, 12))
            ->subDays(random_int(0, 28));
        $birthPlace = Arr::get($overwrite, 'city') ?? Arr::random(self::VALID_CITIES);
        $fiscalCodeGender = $genderValue ? 'M' : 'F';
        $address = $this->faker->address;

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'date_of_birth' => $dob,
            'address' => $address,
            'gender' => $genderValue,
            'fiscal_code' => Arr::get($overwrite, 'fiscal_code') ?? CodiceFiscale::generate(
                $firstName,
                $lastName,
                $dob->format('Y-m-d'),
                $birthPlace,
                $fiscalCodeGender
            ),
            'city' => $birthPlace,
            'cap' => Arr::get($overwrite, 'cap') ?? (string) random_int(10000, 99999),
            'mobile_phone' => Arr::get($overwrite, 'mobile_phone') ?? $this->faker->phoneNumber,
            'user_id' => Arr::get($overwrite, 'user_id')
        ];
    }
}
