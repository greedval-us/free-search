<?php

namespace App\Http\Requests\Shifr;

use App\Http\Requests\LocalizedFormRequest;
use App\Modules\Shifr\DTO\Toolkit\TransformLookupDTO;

final class ShifrTransformRequest extends LocalizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:20000'],
            'operation' => ['required', 'string', 'in:base64_encode,base64_decode,hex_encode,hex_decode,url_encode,url_decode'],
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

    public function locale(): string
    {
        return $this->resolveLocale();
    }
}
