<?php

namespace App\Http\Requests\YouTube;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class YouTubeAnalyticsRequest extends FormRequest
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
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * @return array{mode: string, videoId: string, channelId: string, limit: int}
     */
    public function toLookupParams(): array
    {
        $validated = $this->validated();

        return [
            'mode' => (string) ($validated['mode'] ?? (! empty($validated['channelId']) ? 'channel' : 'video')),
            'videoId' => trim((string) ($validated['videoId'] ?? '')),
            'channelId' => trim((string) ($validated['channelId'] ?? '')),
            'limit' => (int) ($validated['limit'] ?? 10),
        ];
    }
}
