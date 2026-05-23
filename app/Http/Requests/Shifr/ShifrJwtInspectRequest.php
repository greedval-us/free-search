<?php

namespace App\Http\Requests\Shifr;

use App\Modules\Shifr\DTO\Toolkit\JwtLookupDTO;

final class ShifrJwtInspectRequest extends AbstractShifrRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:20000'],
            'secret' => ['nullable', 'string', 'max:2000'],
            'locale' => $this->localeRule(),
        ];
    }

    public function toDto(): JwtLookupDTO
    {
        return new JwtLookupDTO(
            token: (string) $this->validated('token'),
            secret: $this->filled('secret') ? (string) $this->validated('secret') : null,
        );
    }
}
