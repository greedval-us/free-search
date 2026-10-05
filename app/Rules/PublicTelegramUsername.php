<?php

namespace App\Rules;

use App\Modules\Telegram\Access\PublicTelegramSource;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class PublicTelegramUsername implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (PublicTelegramSource::username($value) === null) {
            $fail('validation.regex')->translate();
        }
    }
}
