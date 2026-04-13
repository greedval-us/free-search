<?php

namespace App\Http\Requests\Telegram;

use App\Modules\Telegram\Search\DTO\SearchMediaQueryDTO;
use Illuminate\Foundation\Http\FormRequest;

class StreamTelegramMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function chatUsername(): string
    {
        return ltrim(trim((string) $this->route('chatUsername')), '@');
    }

    public function messageId(): int
    {
        return (int) $this->route('messageId');
    }

    public function toQueryDTO(): SearchMediaQueryDTO
    {
        return new SearchMediaQueryDTO(
            chatUsername: $this->chatUsername(),
            messageId: $this->messageId(),
        );
    }
}
