<?php

namespace App\Http\Requests\Shifr;

use App\Http\Requests\LocalizedFormRequest;
use App\Modules\Shifr\DTO\Toolkit\IocLookupDTO;

final class ShifrIocExtractRequest extends LocalizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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

    public function locale(): string
    {
        return $this->resolveLocale();
    }
}
