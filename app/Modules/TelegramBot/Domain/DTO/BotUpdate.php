<?php

namespace App\Modules\TelegramBot\Domain\DTO;

use App\Modules\TelegramBot\Support\TelegramLimits;

final readonly class BotUpdate
{
    public function __construct(
        public int $id,
        public string $telegramId,
        public string $locale,
        public string $text,
        public ?string $callbackId,
        public string $callbackData,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): ?self
    {
        $callback = $payload['callback_query'] ?? null;
        $message = is_array($callback) ? ($callback['message'] ?? null) : ($payload['message'] ?? null);
        if (! is_array($message) || ! is_int($payload['update_id'] ?? null) || $payload['update_id'] < 0) {
            return null;
        }
        $sender = is_array($callback) ? ($callback['from'] ?? null) : ($message['from'] ?? null);
        $chat = $message['chat'] ?? null;
        if (! is_array($sender) || ! is_array($chat) || ($chat['type'] ?? null) !== 'private'
            || ! is_int($sender['id'] ?? null) || $sender['id'] <= 0
            || ($chat['id'] ?? null) !== $sender['id'] || ($sender['is_bot'] ?? false) !== false) {
            return null;
        }
        $text = $message['text'] ?? '';
        $data = is_array($callback) ? ($callback['data'] ?? '') : '';
        $callbackId = is_array($callback) ? ($callback['id'] ?? null) : null;
        if (! is_string($text) || ! is_string($data) || strlen($data) > TelegramLimits::CALLBACK_BYTES
            || ($callback !== null && (! is_string($callbackId) || strlen($callbackId) > TelegramLimits::CALLBACK_BYTES))) {
            return null;
        }

        return new self($payload['update_id'], (string) $sender['id'],
            is_string($sender['language_code'] ?? null) ? $sender['language_code'] : 'en',
            mb_substr($text, 0, TelegramLimits::MESSAGE_CHARACTERS), $callbackId, $data);
    }
}
