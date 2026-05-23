<?php

namespace App\Http\Requests\Shifr;

use App\Modules\Shifr\DTO\Toolkit\IocLookupDTO;

final class ShifrIocExtractRequest extends AbstractShifrRequest
{
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:40000'],
            'locale' => $this->localeRule(),
        ];
    }

    public function toDto(): IocLookupDTO
    {
        return new IocLookupDTO(
            input: (string) $this->validated('text'),
        );
    }
}
