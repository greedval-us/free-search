<?php

namespace App\Modules\Telegram\Enums;

enum TelegramMediaKind: string
{
    case None = 'none';
    case Photo = 'photo';
    case Video = 'video';
    case Document = 'document';
    case Audio = 'audio';
    case Geo = 'geo';
    case Poll = 'poll';
    case Contact = 'contact';
    case LinkPreview = 'link_preview';
    case Other = 'other';

    public static function fromRawType(?string $rawType): self
    {
        if ($rawType === null || trim($rawType) === '') {
            return self::None;
        }

        $type = strtolower($rawType);

        return match (true) {
            str_contains($type, 'photo') => self::Photo,
            str_contains($type, 'video') => self::Video,
            str_contains($type, 'document') => self::Document,
            str_contains($type, 'audio') => self::Audio,
            str_contains($type, 'geo') => self::Geo,
            str_contains($type, 'poll') => self::Poll,
            str_contains($type, 'contact') => self::Contact,
            str_contains($type, 'webpage') => self::LinkPreview,
            default => self::Other,
        };
    }

    public function hasMedia(): bool
    {
        return $this !== self::None;
    }

    public function label(): string
    {
        return match ($this) {
            self::Photo => 'Фото',
            self::Video => 'Видео',
            self::Document => 'Документ',
            self::Audio => 'Аудио',
            self::Geo => 'Геопозиция',
            self::Poll => 'Опрос',
            self::Contact => 'Контакт',
            self::LinkPreview => 'Ссылка',
            self::Other => 'Медиа',
            self::None => 'Нет',
        };
    }
}
