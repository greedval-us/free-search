<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionActivationToken extends Model
{
    protected $fillable = [
        'token',
        'plan',
        'note',
        'expires_at',
        'used_at',
        'used_by_user_id',
        'used_subscription_id',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $token): void {
            $token->token = $token->token !== null && $token->token !== ''
                ? self::normalizeToken($token->token)
                : self::generateUniqueToken();
            $token->duration_days = 30;
            $token->expires_at ??= CarbonImmutable::now(config('app.timezone'))->addMonth();
        });

        static::updating(function (self $token): void {
            if ($token->isDirty('token')) {
                $token->token = self::normalizeToken((string) $token->token);
            }

            $token->duration_days = 30;
        });
    }

    public static function normalizeToken(string $value): string
    {
        $normalized = strtoupper((string) preg_replace('/[^A-Z0-9]+/i', '', $value));

        return implode('-', str_split($normalized, 4));
    }

    public function usedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by_user_id');
    }

    public function usedSubscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'used_subscription_id');
    }

    private static function generateUniqueToken(): string
    {
        do {
            $token = self::randomToken();
        } while (self::query()->where('token', $token)->exists());

        return $token;
    }

    private static function randomToken(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $segments = [];

        for ($segment = 0; $segment < 4; $segment++) {
            $value = '';

            for ($index = 0; $index < 4; $index++) {
                $value .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            $segments[] = $value;
        }

        return implode('-', $segments);
    }
}
