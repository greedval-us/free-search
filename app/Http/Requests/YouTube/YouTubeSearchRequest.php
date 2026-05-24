<?php

namespace App\Http\Requests\YouTube;

use App\Http\Requests\YouTube\Concerns\ResolvesYouTubeModuleConfig;
use App\Modules\YouTube\DTO\Request\YouTubeSearchQueryDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class YouTubeSearchRequest extends FormRequest
{
    use ResolvesYouTubeModuleConfig;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(['video', 'channel', 'playlist'])],
            'channelId' => ['nullable', 'string', 'max:255'],
            'order' => ['nullable', Rule::in(['relevance', 'date', 'viewCount', 'rating', 'title', 'videoCount'])],
            'publishedAfter' => ['nullable', 'date_format:Y-m-d'],
            'publishedBefore' => ['nullable', 'date_format:Y-m-d'],
            'regionCode' => ['nullable', 'string', 'size:2'],
            'relevanceLanguage' => ['nullable', 'string', 'max:12'],
            'safeSearch' => ['nullable', Rule::in(['moderate', 'none', 'strict'])],
            'videoDuration' => ['nullable', Rule::in(['any', 'short', 'medium', 'long'])],
            'videoDefinition' => ['nullable', Rule::in(['any', 'high', 'standard'])],
            'videoCaption' => ['nullable', Rule::in(['any', 'closedCaption', 'none'])],
            'limit' => ['nullable', 'integer', 'min:1', 'max:' . $this->youtubeModuleConfig()->searchLimitMax()],
            'pageToken' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $from = $this->input('publishedAfter');
            $to = $this->input('publishedBefore');

            if ($from && $to && $from > $to) {
                $validator->errors()->add('publishedAfter', __('Date "from" must be less than or equal to date "to".'));
            }
        });
    }

    public function toDTO(): YouTubeSearchQueryDTO
    {
        $validated = $this->validated();

        return new YouTubeSearchQueryDTO(
            query: trim((string) $validated['q']),
            type: (string) ($validated['type'] ?? 'video'),
            maxResults: min($this->youtubeModuleConfig()->searchLimitMax(), max(1, (int) ($validated['limit'] ?? $this->youtubeModuleConfig()->searchLimitDefault()))),
            order: (string) ($validated['order'] ?? 'relevance'),
            safeSearch: (string) ($validated['safeSearch'] ?? 'moderate'),
            channelId: trim((string) ($validated['channelId'] ?? '')),
            regionCode: trim((string) ($validated['regionCode'] ?? '')),
            relevanceLanguage: trim((string) ($validated['relevanceLanguage'] ?? '')),
            pageToken: trim((string) ($validated['pageToken'] ?? '')),
            videoDuration: (string) ($validated['videoDuration'] ?? 'any'),
            videoDefinition: (string) ($validated['videoDefinition'] ?? 'any'),
            videoCaption: (string) ($validated['videoCaption'] ?? 'any'),
            publishedAfter: ! empty($validated['publishedAfter'])
                ? Carbon::createFromFormat('Y-m-d', $validated['publishedAfter'], $this->youtubeModuleConfig()->timezone())->startOfDay()->toRfc3339String()
                : null,
            publishedBefore: ! empty($validated['publishedBefore'])
                ? Carbon::createFromFormat('Y-m-d', $validated['publishedBefore'], $this->youtubeModuleConfig()->timezone())->endOfDay()->toRfc3339String()
                : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiParams(): array
    {
        return $this->toDTO()->toArray();
    }
}
