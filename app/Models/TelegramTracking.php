<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramTracking extends Model
{
    public const ACTIVE = 'active';

    public const PAUSED = 'paused';

    public const EXPIRED = 'expired';

    public const STOPPED = 'stopped';

    protected $guarded = ['id'];

    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', self::ACTIVE);
    }

    public function scopeUnfinished(Builder $query): void
    {
        $query->whereNull('ended_at');
    }

    public function canRenew(int $windowDays): bool
    {
        return $this->ended_at === null && $this->expires_at->lte(now()->addDays($windowDays));
    }

    protected function casts(): array
    {
        return ['notify_bot' => 'boolean', 'started_at' => 'immutable_datetime', 'expires_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime', 'purge_at' => 'immutable_datetime', 'entitlement_until' => 'immutable_datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sources(): HasMany
    {
        return $this->hasMany(TelegramTrackingSource::class, 'tracking_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TelegramTrackingMessage::class, 'tracking_id');
    }
}
