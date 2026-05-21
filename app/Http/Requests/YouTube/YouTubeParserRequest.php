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
            'videoId' => ['required', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'pageToken' => ['nullable', 'string', 'max:255'],
            'order' => ['nullable', Rule::in(['relevance', 'time'])],
            'searchTerms' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function toDTO(): YouTubeCommentsQueryDTO
    {
        $validated = $this->validated();

        return new YouTubeCommentsQueryDTO(
            videoId: trim((string) $validated['videoId']),
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
}
