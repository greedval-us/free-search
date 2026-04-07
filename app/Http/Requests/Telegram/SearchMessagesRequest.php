<?php

namespace App\Http\Requests\Telegram;

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
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offsetId' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $dateFrom = $this->input('dateFrom');
            $dateTo = $this->input('dateTo');

            if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
                $validator->errors()->add('dateFrom', 'Дата "с" должна быть меньше или равна дате "по".');
            }
        });
    }

    public function chatUsername(): string
    {
        return ltrim(trim((string) $this->validated('chatUsername')), '@');
    }

    public function limitValue(): int
    {
        return (int) ($this->validated('limit') ?? 20);
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
}
