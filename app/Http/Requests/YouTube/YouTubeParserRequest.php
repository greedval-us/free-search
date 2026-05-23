<?php

namespace App\Http\Requests\YouTube;

use App\Modules\YouTube\DTO\Request\YouTubeCommentsQueryDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class YouTubeParserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'videoId' => ['required', 'string', 'max:2048'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'pageToken' => ['nullable', 'string', 'max:2048'],
            'order' => ['nullable', Rule::in(['relevance', 'time'])],
            'searchTerms' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function toDTO(): YouTubeCommentsQueryDTO
    {
        $validated = $this->validated();

        return new YouTubeCommentsQueryDTO(
            videoId: $this->normalizeVideoId(trim((string) $validated['videoId'])),
            maxResults: (int) ($validated['limit'] ?? 20),
            order: (string) ($validated['order'] ?? 'relevance'),
            pageToken: trim((string) ($validated['pageToken'] ?? '')),
            searchTerms: trim((string) ($validated['searchTerms'] ?? '')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiParams(): array
    {
        return $this->toDTO()->toArray();
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
