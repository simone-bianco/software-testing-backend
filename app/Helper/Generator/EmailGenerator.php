<?php

namespace App\Helper\Generator;

use Faker\Provider\Internet;
use Str;

class EmailGenerator
{
    /**
     * @param  string  $firstName
     * @param  string  $lastName
     * @return string|null
     */
    public function generateEmail(string $firstName, string $lastName): ?string
    {
        try {
            return filter_var(Str::lower(
                $firstName . '.' . $lastName . '+' . random_int(0, 999) . '@' . Internet::safeEmailDomain()
            ),
                FILTER_SANITIZE_EMAIL
            );
        } catch (\Exception $exception) {
            return null;
        }
    }
}
