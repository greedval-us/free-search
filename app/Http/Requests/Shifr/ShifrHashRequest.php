<?php

namespace App\Http\Requests\Shifr;

use App\Http\Requests\LocalizedFormRequest;
use App\Modules\Shifr\DTO\Toolkit\HashLookupDTO;
use App\Modules\Shifr\Support\HashAlgorithms;

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
            'algorithm' => ['nullable', 'string', 'in:' . HashAlgorithms::validationRuleList()],
            'hmac_key' => ['nullable', 'string', 'max:1000'],
            'locale' => $this->localeRule(),
        ];
    }

    public function toDto(): HashLookupDTO
    {
        return new HashLookupDTO(
            input: (string) $this->validated('text'),
            algorithm: (string) ($this->validated('algorithm') ?? HashAlgorithms::DEFAULT),
            hmacKey: $this->filled('hmac_key') ? (string) $this->validated('hmac_key') : null,
        );
    }

    public function locale(): string
    {
        return $this->resolveLocale();
    }
}
