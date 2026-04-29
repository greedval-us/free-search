<?php

namespace App\Modules\EmailIntel\Application\Services\EmailIntel;

final class EmailProfileClassifier
{
    /**
     * @var array<int, string>
     */
    private const FREE_PROVIDERS = [
        'gmail.com',
        'googlemail.com',
        'yahoo.com',
        'outlook.com',
        'hotmail.com',
        'live.com',
        'icloud.com',
        'proton.me',
        'protonmail.com',
        'mail.ru',
        'yandex.ru',
        'ya.ru',
        'rambler.ru',
    ];

    /**
     * Keep this list local and intentionally small. It can become config later.
     *
     * @var array<int, string>
     */
    private const DISPOSABLE_DOMAINS = [
        '10minutemail.com',
        'guerrillamail.com',
        'mailinator.com',
        'tempmail.com',
        'temp-mail.org',
        'yopmail.com',
        'trashmail.com',
    ];

    /**
     * @param array<string, mixed> $localPartAnalysis
     * @return array<string, mixed>
     */
    public function classify(string $email, string $domain, array $localPartAnalysis): array
    {
        return [
            'isFreeProvider' => in_array($domain, self::FREE_PROVIDERS, true),
            'isDisposable' => in_array($domain, self::DISPOSABLE_DOMAINS, true),
            'isRoleAccount' => (bool) $localPartAnalysis['isRoleAccount'],
            'gravatarHash' => md5($email),
            'gravatarUrl' => 'https://www.gravatar.com/avatar/' . md5($email) . '?d=404',
        ];
    }
}
