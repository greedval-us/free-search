<?php

namespace App\Http\Requests\Shifr;

use App\Http\Requests\LocalizedFormRequest;

final class ShifrClassicCipherRequest extends LocalizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:20000'],
            'cipher' => ['required', 'string', 'in:caesar,atbash,rot13,rot47'],
            'direction' => ['required', 'string', 'in:encrypt,decrypt,transform'],
            'shift' => ['nullable', 'integer', 'min:-1000', 'max:1000'],
            'locale' => $this->localeRule(),
        ];
    }

    public function text(): string
    {
        return (string) $this->validated('text');
    }

    public function cipher(): string
    {
        return (string) $this->validated('cipher');
    }

    public function direction(): string
    {
        return (string) $this->validated('direction');
    }

    public function shift(): int
    {
        return (int) ($this->validated('shift') ?? 3);
    }

    public function locale(): string
    {
        return $this->resolveLocale();
    }
}
