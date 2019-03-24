<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class nochinese implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
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
        //
     //   echo 'aaaaaaaaaa';
        //var_dump( $value);
      //  dump(preg_match('/[\x{4e00}-\x{9fa5}]/u', $value));
        return !preg_match('/[\x{4e00}-\x{9fa5}]/u', $value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return ' It Can not use chinese!.';
    }
}
