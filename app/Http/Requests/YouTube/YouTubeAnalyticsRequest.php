<?php

namespace App\Http\Requests\YouTube;

use App\Modules\YouTube\DTO\Request\YouTubeAnalyticsLookupDTO;
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

    public function toDTO(): YouTubeAnalyticsLookupDTO
    {
        $validated = $this->validated();

        return new YouTubeAnalyticsLookupDTO(
            mode: (string) ($validated['mode'] ?? (! empty($validated['channelId']) ? 'channel' : 'video')),
            videoId: trim((string) ($validated['videoId'] ?? '')),
            channelId: trim((string) ($validated['channelId'] ?? '')),
            limit: (int) ($validated['limit'] ?? 10),
        );
    }

    /**
     * @return array{mode: string, videoId: string, channelId: string, limit: int}
     */
    public function toLookupParams(): array
    {
        return $this->toDTO()->toArray();
    }
}
