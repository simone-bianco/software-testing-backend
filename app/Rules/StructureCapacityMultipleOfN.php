<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class StructureCapacityMultipleOfN implements Rule
{
    protected int $slice;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(int $slice)
    {
        $this->slice = $slice;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return $value % $this->slice === 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return sprintf('La capacità deve essere un multiplo di %s', $this->slice);
    }
}
