<?php

namespace App\Http\Requests\YouTube;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class YouTubeSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'max:255'],
            'channelId' => ['nullable', 'string', 'max:255'],
            'order' => ['nullable', Rule::in(['date', 'rating', 'relevance', 'title', 'videoCount', 'viewCount'])],
            'publishedAfter' => ['nullable', 'date_format:Y-m-d'],
            'publishedBefore' => ['nullable', 'date_format:Y-m-d'],
            'regionCode' => ['nullable', 'string', 'size:2'],
            'relevanceLanguage' => ['nullable', 'string', 'max:12'],
            'safeSearch' => ['nullable', Rule::in(['moderate', 'none', 'strict'])],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
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

    /**
     * @return array<string, string|int>
     */
    public function toApiParams(): array
    {
        $validated = $this->validated();

        $params = [
            'q' => trim((string) $validated['q']),
            'maxResults' => (int) ($validated['limit'] ?? 12),
            'order' => (string) ($validated['order'] ?? 'relevance'),
            'safeSearch' => (string) ($validated['safeSearch'] ?? 'moderate'),
        ];

        foreach (['channelId', 'regionCode', 'relevanceLanguage', 'pageToken'] as $key) {
            $value = trim((string) ($validated[$key] ?? ''));

            if ($value !== '') {
                $params[$key] = $value;
            }
        }

        if (! empty($validated['publishedAfter'])) {
            $params['publishedAfter'] = Carbon::createFromFormat('Y-m-d', $validated['publishedAfter'])->startOfDay()->toRfc3339String();
        }

        if (! empty($validated['publishedBefore'])) {
            $params['publishedBefore'] = Carbon::createFromFormat('Y-m-d', $validated['publishedBefore'])->endOfDay()->toRfc3339String();
        }

        return $params;
    }
}
