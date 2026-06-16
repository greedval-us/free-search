<?php

namespace App\Http\Requests\Telegram;

use App\Http\Requests\Telegram\Concerns\ResolvesTelegramConfig;
use App\Modules\Telegram\DTO\Request\TelegramParserStartDTO;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TelegramParserStartRequest extends FormRequest
{
    use ResolvesTelegramConfig;

    private const PERIODS = ['day', 'week', 'month', 'custom'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chatUsername' => ['required', 'string', 'max:255'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'period' => ['required', 'string', 'in:' . implode(',', self::PERIODS)],
            'dateFrom' => ['nullable', 'date_format:Y-m-d'],
            'dateTo' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->keyword() !== null) {
                return;
            }

            if ($this->period() !== 'custom') {
                return;
            }

            $dateFrom = $this->input('dateFrom');
            $dateTo = $this->input('dateTo');

            if (!$dateFrom || !$dateTo) {
                $validator->errors()->add('dateTo', __('errors.validation.custom_period_requires_both_dates'));

                return;
            }

            if ($dateFrom > $dateTo) {
                $validator->errors()->add('dateFrom', __('errors.validation.date_from_before_or_equal_date_to'));

                return;
            }

            $from = Carbon::createFromFormat('Y-m-d', $dateFrom, $this->telegramConfig()->timezone())->startOfDay();
            $to = Carbon::createFromFormat('Y-m-d', $dateTo, $this->telegramConfig()->timezone())->endOfDay();

            if ($to->diffInDays($from) > ($this->customRangeMaxDays() - 1)) {
                $validator->errors()->add(
                    'dateTo',
                    __('errors.validation.custom_parser_range_max_days', [
                        'days' => $this->customRangeMaxDays(),
                    ])
                );
            }
        });
    }

    public function chatUsername(): string
    {
        return ltrim(trim((string) $this->validated('chatUsername')), '@');
    }

    public function keyword(): ?string
    {
        $keyword = trim((string) ($this->validated('keyword') ?? ''));

        return $keyword !== '' ? $keyword : null;
    }

    public function period(): string
    {
        $period = strtolower(trim((string) ($this->validated('period') ?? 'week')));

        return in_array($period, self::PERIODS, true) ? $period : 'week';
    }

    /**
     * @return array{dateFrom: string|null, dateTo: string|null, minTimestamp: int|null, maxTimestamp: int|null}
     */
    public function range(): array
    {
        if ($this->keyword() !== null) {
            return [
                'dateFrom' => null,
                'dateTo' => null,
                'minTimestamp' => null,
                'maxTimestamp' => null,
            ];
        }

        $timezone = $this->telegramConfig()->timezone();
        $period = $this->period();

        if ($period === 'custom') {
            $from = Carbon::createFromFormat('Y-m-d', (string) $this->validated('dateFrom'), $timezone)->startOfDay();
            $to = Carbon::createFromFormat('Y-m-d', (string) $this->validated('dateTo'), $timezone)->endOfDay();

            return [
                'dateFrom' => $from->toDateString(),
                'dateTo' => $to->toDateString(),
                'minTimestamp' => $from->timestamp,
                'maxTimestamp' => $to->timestamp,
            ];
        }

        $to = Carbon::now($timezone)->endOfDay();
        $days = match ($period) {
            'day' => 1,
            'week' => 7,
            'month' => 30,
            default => 7,
        };
        $from = $to->copy()->subDays($days - 1)->startOfDay();

        return [
            'dateFrom' => $from->toDateString(),
            'dateTo' => $to->toDateString(),
            'minTimestamp' => $from->timestamp,
            'maxTimestamp' => $to->timestamp,
        ];
    }

    public function toStartDTO(): TelegramParserStartDTO
    {
        return new TelegramParserStartDTO(
            userId: (int) $this->user()->id,
            chatUsername: $this->chatUsername(),
            period: $this->period(),
            keyword: $this->keyword(),
            range: $this->range(),
        );
    }

    private function customRangeMaxDays(): int
    {
        return $this->telegramConfig()->parserCustomRangeMaxDays();
    }
}
