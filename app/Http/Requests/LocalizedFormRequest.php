<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class LocalizedFormRequest extends FormRequest
{
    /**
     * @var array<int, string>
     */
    protected const SUPPORTED_LOCALES = ['ru', 'en'];

    protected const DEFAULT_LOCALE = 'en';

    /**
     * @return array<int, string>
     */
    protected function localeRule(): array
    {
        return ['nullable', 'string', 'in:' . implode(',', self::SUPPORTED_LOCALES)];
    }

    protected function resolveLocale(): string
    {
        $locale = strtolower(trim((string) ($this->validated('locale') ?? app()->getLocale())));

        return in_array($locale, self::SUPPORTED_LOCALES, true) ? $locale : self::DEFAULT_LOCALE;
    }
}

