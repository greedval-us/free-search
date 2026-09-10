<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramTrackingSource extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['session_name', 'lease_token', 'lease_until'];

    public function scopeLeaseAvailable(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query->whereNull('lease_until')->orWhere('lease_until', '<=', now()));
    }

    public function isClaimedBy(string $token): bool
    {
        return $this->lease_token === $token && $this->tracking->status === TelegramTracking::ACTIVE;
    }

    protected function casts(): array
    {
        return ['cursor_id' => 'integer', 'offset_id' => 'integer', 'high_id' => 'integer',
            'collect_from' => 'immutable_datetime', 'window_end' => 'immutable_datetime',
            'checked_at' => 'immutable_datetime', 'next_check_at' => 'immutable_datetime', 'lease_until' => 'immutable_datetime'];
    }

    public function tracking(): BelongsTo
    {
        return $this->belongsTo(TelegramTracking::class, 'tracking_id');
    }
}
