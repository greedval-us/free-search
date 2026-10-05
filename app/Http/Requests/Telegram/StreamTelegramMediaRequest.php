<?php

namespace App\Http\Requests\Telegram;

use App\Modules\Telegram\DTO\Request\SearchMediaQueryDTO;
use App\Rules\PublicTelegramUsername;
use Illuminate\Foundation\Http\FormRequest;

class StreamTelegramMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chatUsername' => ['bail', 'required', 'string', new PublicTelegramUsername],
            'messageId' => ['required', 'integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'chatUsername' => $this->route('chatUsername'),
            'messageId' => $this->route('messageId'),
        ]);
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
