<?php

namespace App\Http\Requests\Telegram;

use App\Modules\Telegram\DTO\Request\SearchMessagesQueryDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class SearchMessagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chatUsername' => ['required', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:255'],
            'fromUsername' => ['nullable', 'string', 'max:255'],
            'dateFrom' => ['nullable', 'date_format:Y-m-d'],
            'dateTo' => ['nullable', 'date_format:Y-m-d'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:' . $this->messagesLimitMax()],
            'offsetId' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $dateFrom = $this->input('dateFrom');
            $dateTo = $this->input('dateTo');

            if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
                $validator->errors()->add('dateFrom', __('Date "from" must be less than or equal to date "to".'));
            }
        });
    }

    public function chatUsername(): string
    {
        return ltrim(trim((string) $this->validated('chatUsername')), '@');
    }

    public function limitValue(): int
    {
        return (int) ($this->validated('limit') ?? $this->messagesLimitDefault());
    }

    public function offsetId(): int
    {
        return (int) ($this->validated('offsetId') ?? 0);
    }

    public function telegramFilter(): array
    {
        $validated = $this->validated();

        $filter = [
            'peer' => trim((string) $validated['chatUsername']),
            'q' => trim((string) ($validated['q'] ?? '')),
            'limit' => $this->limitValue(),
            'offset_id' => $this->offsetId(),
        ];

        $fromUsername = trim((string) ($validated['fromUsername'] ?? ''));
        if ($fromUsername !== '') {
            $filter['from_id'] = ltrim($fromUsername, '@');
        }

        if (!empty($validated['dateFrom'])) {
            $filter['min_date'] = Carbon::createFromFormat('Y-m-d', $validated['dateFrom'])->startOfDay()->timestamp;
        }

        if (!empty($validated['dateTo'])) {
            $filter['max_date'] = Carbon::createFromFormat('Y-m-d', $validated['dateTo'])->endOfDay()->timestamp;
        }

        return $filter;
    }

    public function toQueryDTO(): SearchMessagesQueryDTO
    {
        return new SearchMessagesQueryDTO(
            filter: $this->telegramFilter(),
            limit: $this->limitValue(),
            offsetId: $this->offsetId(),
            chatUsername: $this->chatUsername(),
        );
    }

    private function isNumericAuthorFilter(string $value): bool
    {
        $normalized = ltrim(trim($value), '@');

        return $normalized !== '' && ctype_digit($normalized);
    }

    private function messagesLimitDefault(): int
    {
        return max(1, (int) config('osint.telegram.search.messages_limit_default', 20));
    }

    private function messagesLimitMax(): int
    {
        return max($this->messagesLimitDefault(), (int) config('osint.telegram.search.messages_limit_max', 100));
    }
}
