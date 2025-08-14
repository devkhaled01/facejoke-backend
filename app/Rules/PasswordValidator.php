<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class PasswordValidator implements Rule
{
    public function passes($attribute, $value)
    {
        // Minimum 8 characters, only letters and numbers
        return preg_match('/^[A-Za-z\d]{8,}$/', $value);
    }

    public function message()
    {
        return 'The :attribute must be at least 8 characters and contain only letters and numbers.';
    }
} 