<?php

namespace App\Http\Requests\Telegram;

use App\Http\Requests\LocalizedFormRequest;
use App\Http\Requests\Telegram\Concerns\ResolvesTelegramConfig;
use App\Modules\Telegram\DTO\Request\TelegramAnalyticsParamsDTO;
use Carbon\Carbon;
use Illuminate\Validation\Validator;

class TelegramAnalyticsRequest extends LocalizedFormRequest
{
    use ResolvesTelegramConfig;

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
            'locale' => $this->localeRule(),
            'periodDays' => ['nullable', 'integer', 'min:' . $this->periodMinDays(), 'max:' . $this->periodMaxDays()],
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
                $rangeStart = Carbon::createFromFormat('Y-m-d', $dateFrom, $this->telegramConfig()->timezone())->startOfDay();
                $rangeEnd = Carbon::createFromFormat('Y-m-d', $dateTo, $this->telegramConfig()->timezone())->endOfDay();

                if ($rangeEnd->diffInDays($rangeStart) > ($this->customRangeMaxDays() - 1)) {
                    $validator->errors()->add(
                        'dateTo',
                        __('The custom analytics range cannot exceed :days days.', [
                            'days' => $this->customRangeMaxDays(),
                        ])
                    );
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
        return max(
            $this->periodMinDays(),
            min($this->periodMaxDays(), (int) ($this->validated('periodDays') ?? $this->periodMaxDays()))
        );
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

        return Carbon::createFromFormat('Y-m-d', $dateFrom, $this->telegramConfig()->timezone())->startOfDay();
    }

    public function dateTo(): ?Carbon
    {
        $dateTo = $this->validated('dateTo');

        if (blank($dateTo)) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d', $dateTo, $this->telegramConfig()->timezone())->endOfDay();
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
        return $this->resolveLocale();
    }

    public function toParamsDTO(): TelegramAnalyticsParamsDTO
    {
        return new TelegramAnalyticsParamsDTO(
            chatUsername: $this->chatUsername(),
            scorePriority: $this->scorePriority(),
            keyword: $this->keyword(),
        );
    }

    private function periodMinDays(): int
    {
        return $this->telegramConfig()->analyticsPeriodMinDays();
    }

    private function periodMaxDays(): int
    {
        return $this->telegramConfig()->analyticsPeriodMaxDays();
    }

    private function customRangeMaxDays(): int
    {
        return $this->telegramConfig()->analyticsCustomRangeMaxDays();
    }
}
