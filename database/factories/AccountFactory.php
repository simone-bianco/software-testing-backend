<?php

namespace Database\Factories;

use App\Models\Account;
use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Account::class;

    /**
     * @return array
     * @throws Exception
     */
    public function definition(): array
    {
        return [
        ];
    }
}
