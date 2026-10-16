<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPin implements ValidationRule
{
    public const WEAK_PINS = [
        // 4 digits
        '0000', '1111', '2222', '3333', '4444', '5555', '6666', '7777', '8888', '9999',
        '1234', '4321', '0123', '9876',
        // 5 digits
        '00000', '11111', '22222', '33333', '44444', '55555', '66666', '77777', '88888', '99999',
        '12345', '54321',
        // 6 digits
        '000000', '111111', '222222', '333333', '444444', '555555', '666666', '777777', '888888', '999999',
        '123456', '654321', '012345', '987654',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $val = (string) $value;

        if (! ctype_digit($val) || strlen($val) < 4 || strlen($val) > 6) {
            $fail(__('messages.auth.pin_digits_between'));

            return;
        }

        if (in_array($val, self::WEAK_PINS, true) || count(array_unique(str_split($val))) === 1) {
            $fail(__('messages.auth.pin_too_weak'));
        }
    }
}
