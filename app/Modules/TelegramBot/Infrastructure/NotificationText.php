<?php

namespace App\Modules\TelegramBot\Infrastructure;

use Illuminate\Filesystem\Filesystem;

final readonly class NotificationText
{
    public function __construct(private Filesystem $files) {}

    /** @param array<string, mixed> $payload */
    public function render(array $payload, string $locale): string
    {
        // Share the site's notification dictionary instead of maintaining a second copy.
        $dictionary = $this->files->json(resource_path('js/locales/'.($locale === 'ru' ? 'ru' : 'en').'/systemNotifications.json'));
        $parts = [];
        foreach (['title', 'body'] as $field) {
            $key = (string) ($payload[$field.'_key'] ?? '');
            $text = str_starts_with($key, 'systemNotifications.') ? data_get($dictionary, substr($key, strlen('systemNotifications.'))) : null;
            $text = is_string($text) ? $text : (string) ($payload[$field] ?? ($field === 'body' ? ($payload['message'] ?? '') : config('app.name')));
            foreach (($payload[$field.'_params'] ?? []) as $name => $value) {
                if (is_scalar($value)) {
                    $text = str_replace('{'.$name.'}', (string) $value, $text);
                }
            }
            $parts[] = strip_tags($text);
        }

        return implode("\n\n", $parts);
    }
}
