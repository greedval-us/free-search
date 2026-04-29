<?php

namespace App\Http\Requests\EmailIntel;

use App\Http\Requests\LocalizedFormRequest;
use App\Modules\EmailIntel\Application\Services\EmailIntel\EmailBulkIntelService;

class EmailIntelBulkLookupRequest extends LocalizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'emails' => ['required', 'string', 'max:8000'],
            'locale' => $this->localeRule(),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function emails(): array
    {
        $emails = preg_split('/[\s,;]+/', (string) $this->validated('emails')) ?: [];
        $emails = array_map(static fn (string $email): string => mb_strtolower(trim($email)), $emails);
        $emails = array_values(array_filter($emails, static fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false));

        return array_slice(array_values(array_unique($emails)), 0, EmailBulkIntelService::MAX_EMAILS);
    }

    public function locale(): string
    {
        return $this->resolveLocale();
    }
}
