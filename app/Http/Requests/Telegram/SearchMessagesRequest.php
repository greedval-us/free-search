<?php

namespace App\Http\Requests\Telegram;

use App\Http\Requests\Telegram\Concerns\ResolvesTelegramConfig;
use App\Modules\Telegram\DTO\Request\SearchMessagesQueryDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class SearchMessagesRequest extends FormRequest
{
    use ResolvesTelegramConfig;

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
            $normalizedAuthor = ltrim($fromUsername, '@');

            if (ctype_digit($normalizedAuthor)) {
                $authorId = (int) $normalizedAuthor;

                if ($authorId > 0) {
                    $filter['authorId'] = $authorId;
                }
            } elseif ($normalizedAuthor !== '') {
                $filter['from_id'] = $normalizedAuthor;
            }
        }

        if (!empty($validated['dateFrom'])) {
            $filter['min_date'] = Carbon::createFromFormat('Y-m-d', $validated['dateFrom'], $this->telegramConfig()->timezone())->startOfDay()->timestamp;
        }

        if (!empty($validated['dateTo'])) {
            $filter['max_date'] = Carbon::createFromFormat('Y-m-d', $validated['dateTo'], $this->telegramConfig()->timezone())->endOfDay()->timestamp;
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

    private function messagesLimitDefault(): int
    {
        return $this->telegramConfig()->searchMessagesLimitDefault();
    }

    private function messagesLimitMax(): int
    {
        return $this->telegramConfig()->searchMessagesLimitMax();
    }
}
