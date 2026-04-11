<?php

namespace App\Http\Requests\Telegram;

use Illuminate\Foundation\Http\FormRequest;

class TelegramParserExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chatUsername' => ['required', 'string', 'max:255'],
            'period' => ['required', 'string', 'max:32'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'range' => ['nullable', 'array'],
            'range.dateFrom' => ['nullable', 'date'],
            'range.dateTo' => ['nullable', 'date'],
            'isChannel' => ['nullable', 'boolean'],
            'messagesCount' => ['nullable', 'integer', 'min:0'],
            'commentsCount' => ['nullable', 'integer', 'min:0'],
            'messages' => ['required', 'array'],
            'messages.*' => ['array'],
            'commentsIndex' => ['nullable', 'array'],
            'commentsIndex.*' => ['array'],
            'reactionsIndex' => ['nullable', 'array'],
            'reactionsIndex.*' => ['array'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $validated = $this->validated();

        return [
            'chatUsername' => ltrim(trim((string) ($validated['chatUsername'] ?? '')), '@'),
            'period' => (string) ($validated['period'] ?? ''),
            'keyword' => ($validated['keyword'] ?? null) !== null ? (string) $validated['keyword'] : null,
            'range' => is_array($validated['range'] ?? null) ? $validated['range'] : [],
            'isChannel' => (bool) ($validated['isChannel'] ?? false),
            'messagesCount' => (int) ($validated['messagesCount'] ?? 0),
            'commentsCount' => (int) ($validated['commentsCount'] ?? 0),
            'messages' => is_array($validated['messages'] ?? null) ? $validated['messages'] : [],
            'commentsIndex' => is_array($validated['commentsIndex'] ?? null) ? $validated['commentsIndex'] : [],
            'reactionsIndex' => is_array($validated['reactionsIndex'] ?? null) ? $validated['reactionsIndex'] : [],
        ];
    }
}

