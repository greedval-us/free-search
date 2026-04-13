<?php

namespace App\Http\Requests\Telegram;

use App\Modules\Telegram\DTO\Request\TelegramAnalyticsParamsDTO;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TelegramAnalyticsRequest extends FormRequest
{
    private const SCORE_PRIORITIES = [
        'balanced',
        'reach',
        'discussion',
        'virality',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chatUsername' => ['required', 'string', 'max:255'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'locale' => ['nullable', 'string', 'in:ru,en'],
            'periodDays' => ['nullable', 'integer', 'min:1', 'max:7'],
            'dateFrom' => ['nullable', 'date_format:Y-m-d'],
            'dateTo' => ['nullable', 'date_format:Y-m-d'],
            'scorePriority' => ['nullable', 'string', 'in:' . implode(',', self::SCORE_PRIORITIES)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $dateFrom = $this->input('dateFrom');
            $dateTo = $this->input('dateTo');

            if (($dateFrom && !$dateTo) || (!$dateFrom && $dateTo)) {
                $validator->errors()->add('dateTo', __('Please provide both dates for a custom range.'));
            }

            if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
                $validator->errors()->add('dateFrom', __('Date "from" must be less than or equal to date "to".'));
            }

            if ($dateFrom && $dateTo) {
                $rangeStart = Carbon::createFromFormat('Y-m-d', $dateFrom, config('app.timezone'))->startOfDay();
                $rangeEnd = Carbon::createFromFormat('Y-m-d', $dateTo, config('app.timezone'))->endOfDay();

                if ($rangeEnd->diffInDays($rangeStart) > 6) {
                    $validator->errors()->add('dateTo', __('The custom analytics range cannot exceed 7 days.'));
                }
            }
        });
    }

    public function chatUsername(): string
    {
        return ltrim(trim((string) $this->validated('chatUsername')), '@');
    }

    public function periodDays(): int
    {
        return max(1, min(7, (int) ($this->validated('periodDays') ?? 7)));
    }

    public function customRange(): bool
    {
        return filled($this->validated('dateFrom')) && filled($this->validated('dateTo'));
    }

    public function dateFrom(): ?Carbon
    {
        $dateFrom = $this->validated('dateFrom');

        if (blank($dateFrom)) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d', $dateFrom, config('app.timezone'))->startOfDay();
    }

    public function dateTo(): ?Carbon
    {
        $dateTo = $this->validated('dateTo');

        if (blank($dateTo)) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d', $dateTo, config('app.timezone'))->endOfDay();
    }

    public function scorePriority(): string
    {
        $priority = (string) ($this->validated('scorePriority') ?? 'balanced');

        return in_array($priority, self::SCORE_PRIORITIES, true) ? $priority : 'balanced';
    }

    public function keyword(): ?string
    {
        $keyword = trim((string) ($this->validated('keyword') ?? ''));

        return $keyword !== '' ? $keyword : null;
    }

    public function locale(): string
    {
        $locale = strtolower(trim((string) ($this->validated('locale') ?? app()->getLocale())));

        return in_array($locale, ['ru', 'en'], true) ? $locale : 'en';
    }

    public function toParamsDTO(): TelegramAnalyticsParamsDTO
    {
        return new TelegramAnalyticsParamsDTO(
            chatUsername: $this->chatUsername(),
            scorePriority: $this->scorePriority(),
            keyword: $this->keyword(),
        );
    }
}

