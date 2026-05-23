<?php

namespace App\Http\Requests\YouTube;

use App\Http\Requests\LocalizedFormRequest;
use App\Modules\YouTube\DTO\Request\YouTubeAnalyticsLookupDTO;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class YouTubeAnalyticsRequest extends LocalizedFormRequest
{
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
            'periodDays' => ['nullable', 'integer', Rule::in([1, 3, 7])],
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
                $from = Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay();
                $to = Carbon::createFromFormat('Y-m-d', $dateTo)->endOfDay();
            } catch (\Throwable) {
                return;
            }

            if ($from->greaterThan($to)) {
                $validator->errors()->add('dateFrom', __('Date "from" must be less than or equal to date "to".'));
                return;
            }

            if ($from->diffInDays($to) > 6) {
                $validator->errors()->add('dateTo', __('Custom period cannot be longer than 7 days.'));
            }
        });
    }

    public function toDTO(): YouTubeAnalyticsLookupDTO
    {
        $validated = $this->validated();
        $rawVideoInput = trim((string) ($validated['videoId'] ?? ''));
        $normalizedVideoId = $this->normalizeVideoId($rawVideoInput);
        $dateFrom = trim((string) ($validated['dateFrom'] ?? ''));
        $dateTo = trim((string) ($validated['dateTo'] ?? ''));
        $hasCustomRange = $dateFrom !== '' && $dateTo !== '';

        return new YouTubeAnalyticsLookupDTO(
            mode: (string) ($validated['mode'] ?? (! empty($validated['channelId']) ? 'channel' : 'video')),
            videoId: $normalizedVideoId,
            channelId: trim((string) ($validated['channelId'] ?? '')),
            periodDays: (int) ($validated['periodDays'] ?? 7),
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

    private function normalizeVideoId(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (!str_contains($value, '://') && !str_contains($value, 'youtube.com') && !str_contains($value, 'youtu.be')) {
            return $value;
        }

        $parts = parse_url($value);

        if (!is_array($parts)) {
            return $value;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = trim((string) ($parts['path'] ?? ''), '/');
        parse_str((string) ($parts['query'] ?? ''), $query);

        if (str_contains($host, 'youtu.be') && $path !== '') {
            return $path;
        }

        if (str_contains($host, 'youtube.com')) {
            $watchId = trim((string) ($query['v'] ?? ''));
            if ($watchId !== '') {
                return $watchId;
            }

            if (str_starts_with($path, 'shorts/')) {
                return trim(substr($path, strlen('shorts/')));
            }

            if (str_starts_with($path, 'embed/')) {
                return trim(substr($path, strlen('embed/')));
            }
        }

        return $value;
    }
}
