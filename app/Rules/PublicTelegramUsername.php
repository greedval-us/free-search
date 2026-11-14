<?php

namespace App\Rules;

use App\Modules\Telegram\Access\PublicTelegramSource;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class PublicTelegramUsername implements ValidationRule
{
    public function __construct(private readonly string $messageKey = 'validation.regex') {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (PublicTelegramSource::username($value) === null) {
            $fail($this->messageKey)->translate();
        }
    }
}
