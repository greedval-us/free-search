<?php

namespace App\Http\Requests\YouTube;

use App\Http\Requests\LocalizedFormRequest;
use App\Http\Requests\YouTube\Concerns\ResolvesYouTubeModuleConfig;
use App\Modules\YouTube\DTO\Request\YouTubeAnalyticsLookupDTO;
use App\Modules\YouTube\Support\YouTubeInputNormalizer;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class YouTubeAnalyticsRequest extends LocalizedFormRequest
{
    use ResolvesYouTubeModuleConfig;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mode' => ['nullable', Rule::in(['video', 'channel'])],
            'videoId' => ['nullable', 'string', 'max:255', 'required_without:channelId'],
            'channelId' => ['nullable', 'string', 'max:255', 'required_without:videoId'],
            'periodDays' => ['nullable', 'integer', Rule::in($this->youtubeModuleConfig()->analyticsPeriodDays())],
            'dateFrom' => ['nullable', 'date_format:Y-m-d'],
            'dateTo' => ['nullable', 'date_format:Y-m-d'],
            'locale' => $this->localeRule(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $mode = (string) ($this->input('mode') ?? '');
            $dateFrom = trim((string) ($this->input('dateFrom') ?? ''));
            $dateTo = trim((string) ($this->input('dateTo') ?? ''));

            if ($mode !== 'channel' || ($dateFrom === '' && $dateTo === '')) {
                return;
            }

            if ($dateFrom === '' || $dateTo === '') {
                return;
            }

            try {
                $from = Carbon::createFromFormat('Y-m-d', $dateFrom, $this->youtubeModuleConfig()->timezone())->startOfDay();
                $to = Carbon::createFromFormat('Y-m-d', $dateTo, $this->youtubeModuleConfig()->timezone())->endOfDay();
            } catch (\Throwable) {
                return;
            }

            if ($from->greaterThan($to)) {
                $validator->errors()->add('dateFrom', __('Date "from" must be less than or equal to date "to".'));
                return;
            }

            if ($from->diffInDays($to) > ($this->youtubeModuleConfig()->analyticsCustomRangeMaxDays() - 1)) {
                $validator->errors()->add(
                    'dateTo',
                    __('Custom period cannot be longer than :days days.', ['days' => $this->youtubeModuleConfig()->analyticsCustomRangeMaxDays()])
                );
            }
        });
    }

    public function toDTO(): YouTubeAnalyticsLookupDTO
    {
        $validated = $this->validated();
        $rawVideoInput = trim((string) ($validated['videoId'] ?? ''));
        $normalizedVideoId = YouTubeInputNormalizer::normalizeVideoId($rawVideoInput);
        $dateFrom = trim((string) ($validated['dateFrom'] ?? ''));
        $dateTo = trim((string) ($validated['dateTo'] ?? ''));
        $hasCustomRange = $dateFrom !== '' && $dateTo !== '';

        return new YouTubeAnalyticsLookupDTO(
            mode: (string) ($validated['mode'] ?? (! empty($validated['channelId']) ? 'channel' : 'video')),
            videoId: $normalizedVideoId,
            channelId: trim((string) ($validated['channelId'] ?? '')),
            periodDays: (int) ($validated['periodDays'] ?? $this->youtubeModuleConfig()->analyticsDefaultPeriodDays()),
            dateFrom: $hasCustomRange ? $dateFrom : '',
            dateTo: $hasCustomRange ? $dateTo : '',
        );
    }

    /**
     * @return array{mode: string, videoId: string, channelId: string, periodDays: int, dateFrom: string, dateTo: string}
     */
    public function toLookupParams(): array
    {
        return $this->toDTO()->toArray();
    }

    public function locale(): string
    {
        return $this->resolveLocale();
    }

}
