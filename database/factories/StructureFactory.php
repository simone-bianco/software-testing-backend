<?php

namespace Database\Factories;

use App\Models\Structure;
use Faker\Provider\it_IT\Address;
use Faker\Provider\it_IT\PhoneNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

class StructureFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Structure::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $this->faker->addProvider(new PhoneNumber($this->faker));
        $this->faker->addProvider(new Address($this->faker));

        return [
            'name'=>$this->faker->randomAscii,
            'region' => 'Abruzzo',
            'capacity'=> 24,
            'phone_number'=>$this->faker->phoneNumber,
            'address'=>$this->faker->address,
        ];
    }
}
