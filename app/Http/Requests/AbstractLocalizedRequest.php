<?php

namespace App\Http\Requests;

abstract class AbstractLocalizedRequest extends LocalizedFormRequest
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

