<?php

namespace App\Http\Requests\YouTube;

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

    /**
     * @return array<string, string|int>
     */
    public function toApiParams(): array
    {
        $validated = $this->validated();

        $params = [
            'videoId' => trim((string) $validated['videoId']),
            'maxResults' => (int) ($validated['limit'] ?? 20),
            'order' => (string) ($validated['order'] ?? 'relevance'),
        ];

        foreach (['pageToken', 'searchTerms'] as $key) {
            $value = trim((string) ($validated[$key] ?? ''));

            if ($value !== '') {
                $params[$key] = $value;
            }
        }

        return $params;
    }
}
