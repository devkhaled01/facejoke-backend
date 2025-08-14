<?php

namespace App\Otp;

use SadiqSalau\LaravelOtp\Contracts\OtpInterface as Otp;

class UserRegistrationOtp implements Otp
{
    /**
     * Constructs Otp class
     */
    public function __construct(
        protected string $name,
        protected string $email,
        protected string $password
    )
    {
        //
    }

    /**
     * Processes the Otp
     *
     * @return mixed
     */
    public function process()
    {
        //
    }
}
