<?php

namespace App\Http\Requests\YouTube;

use App\Http\Requests\YouTube\Concerns\ResolvesYouTubeModuleConfig;
use App\Modules\YouTube\DTO\Request\YouTubeCommentsQueryDTO;
use App\Modules\YouTube\Support\YouTubeInputNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class YouTubeParserRequest extends FormRequest
{
    use ResolvesYouTubeModuleConfig;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'videoId' => ['required', 'string', 'max:2048'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:' . $this->youtubeModuleConfig()->parserCommentsLimitMax()],
            'pageToken' => ['nullable', 'string', 'max:2048'],
            'order' => ['nullable', Rule::in(['relevance', 'time'])],
            'searchTerms' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function toDTO(): YouTubeCommentsQueryDTO
    {
        $validated = $this->validated();

        return new YouTubeCommentsQueryDTO(
            videoId: YouTubeInputNormalizer::normalizeVideoId(trim((string) $validated['videoId'])),
            maxResults: (int) ($validated['limit'] ?? $this->youtubeModuleConfig()->parserCommentsLimitDefault()),
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

}
