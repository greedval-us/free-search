<?php

namespace App\Http\Requests\Shifr;

use App\Http\Requests\LocalizedFormRequest;
use App\Modules\Shifr\DTO\Classic\ClassicCipherLookupDTO;

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
            'cipher' => ['required', 'string', 'in:caesar,atbash,rot13,rot47,rot5,vigenere,rail_fence,xor,affine,playfair,columnar,morse'],
            'direction' => ['required', 'string', 'in:encrypt,decrypt,transform'],
            'shift' => ['nullable', 'integer', 'min:-1000', 'max:1000'],
            'key' => ['nullable', 'string', 'max:200'],
            'rails' => ['nullable', 'integer', 'min:2', 'max:20'],
            'xor_key' => ['nullable', 'string', 'max:200'],
            'affine_a' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'affine_b' => ['nullable', 'integer', 'min:-1000', 'max:1000'],
            'playfair_key' => ['nullable', 'string', 'max:200'],
            'column_key' => ['nullable', 'string', 'max:200'],
            'morse_separator' => ['nullable', 'string', 'max:5'],
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

    public function key(): string
    {
        return trim((string) ($this->validated('key') ?? ''));
    }

    public function rails(): int
    {
        return (int) ($this->validated('rails') ?? 3);
    }

    public function locale(): string
    {
        return $this->resolveLocale();
    }

    public function xorKey(): string
    {
        return trim((string) ($this->validated('xor_key') ?? ''));
    }

    public function affineA(): int
    {
        return (int) ($this->validated('affine_a') ?? 5);
    }

    public function affineB(): int
    {
        return (int) ($this->validated('affine_b') ?? 8);
    }

    public function playfairKey(): string
    {
        return trim((string) ($this->validated('playfair_key') ?? ''));
    }

    public function columnKey(): string
    {
        return trim((string) ($this->validated('column_key') ?? ''));
    }

    public function morseSeparator(): string
    {
        return trim((string) ($this->validated('morse_separator') ?? '/'));
    }

    public function toDto(): ClassicCipherLookupDTO
    {
        return new ClassicCipherLookupDTO(
            text: $this->text(),
            cipher: $this->cipher(),
            direction: $this->direction(),
            shift: $this->shift(),
            key: $this->key(),
            rails: $this->rails(),
            xorKey: $this->xorKey(),
            affineA: $this->affineA(),
            affineB: $this->affineB(),
            playfairKey: $this->playfairKey(),
            columnKey: $this->columnKey(),
            morseSeparator: $this->morseSeparator(),
        );
    }
}
