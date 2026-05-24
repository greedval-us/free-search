<?php

namespace App\Http\Requests\Telegram;

use App\Http\Requests\Telegram\Concerns\ResolvesTelegramConfig;
use App\Modules\Telegram\DTO\Request\SearchCommentsQueryDTO;
use Illuminate\Foundation\Http\FormRequest;

class SearchCommentsRequest extends FormRequest
{
    use ResolvesTelegramConfig;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chatUsername' => ['required', 'string', 'max:255'],
            'postId' => ['required', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:' . $this->commentsLimitMax()],
            'offsetId' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function chatUsername(): string
    {
        return ltrim(trim((string) $this->validated('chatUsername')), '@');
    }

    public function postId(): int
    {
        return (int) $this->validated('postId');
    }

    public function limitValue(): int
    {
        return (int) ($this->validated('limit') ?? $this->commentsLimitDefault());
    }

    public function offsetId(): int
    {
        return (int) ($this->validated('offsetId') ?? 0);
    }

    public function toQueryDTO(): SearchCommentsQueryDTO
    {
        return new SearchCommentsQueryDTO(
            chatUsername: $this->chatUsername(),
            postId: $this->postId(),
            limit: $this->limitValue(),
            offsetId: $this->offsetId(),
        );
    }

    private function commentsLimitDefault(): int
    {
        return $this->telegramConfig()->searchCommentsLimitDefault();
    }

    private function commentsLimitMax(): int
    {
        return $this->telegramConfig()->searchCommentsLimitMax();
    }
}
