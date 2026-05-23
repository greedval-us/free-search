<?php

namespace App\Http\Requests\Shifr;

use App\Modules\Shifr\DTO\Toolkit\TransformLookupDTO;

final class ShifrTransformRequest extends AbstractShifrRequest
{
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:20000'],
            'operation' => ['required', 'string', 'in:base64_encode,base64_decode,base64url_encode,base64url_decode,hex_encode,hex_decode,url_encode,url_decode,html_encode,html_decode'],
            'locale' => $this->localeRule(),
        ];
    }

    public function toDto(): TransformLookupDTO
    {
        return new TransformLookupDTO(
            input: (string) $this->validated('text'),
            operation: (string) $this->validated('operation'),
        );
    }
}
