<?php

namespace App\Http\Requests\Shifr;

use App\Http\Requests\LocalizedFormRequest;
use App\Modules\Shifr\DTO\Toolkit\HashLookupDTO;

final class ShifrHashRequest extends LocalizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:20000'],
            'algorithm' => ['nullable', 'string', 'in:md5,sha1,sha256,sha512'],
            'hmac_key' => ['nullable', 'string', 'max:1000'],
            'locale' => $this->localeRule(),
        ];
    }

    public function toDto(): HashLookupDTO
    {
        return new HashLookupDTO(
            input: (string) $this->validated('text'),
            algorithm: (string) ($this->validated('algorithm') ?? 'sha256'),
            hmacKey: $this->filled('hmac_key') ? (string) $this->validated('hmac_key') : null,
        );
    }

    public function locale(): string
    {
        return $this->resolveLocale();
    }
}
