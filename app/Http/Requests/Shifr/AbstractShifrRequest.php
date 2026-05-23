<?php

namespace App\Http\Requests\Shifr;

use App\Http\Requests\LocalizedFormRequest;

abstract class AbstractShifrRequest extends LocalizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function locale(): string
    {
        return $this->resolveLocale();
    }
}

