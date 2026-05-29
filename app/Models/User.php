<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\Access\Enums\AccountPlan;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable([
    'name',
    'email',
    'password',
    'account_type',
    'telegram_id',
    'is_blocked',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Account types.
     */
    public const ACCOUNT_TYPE_USER = 'user';

    public const ACCOUNT_TYPE_ADMIN = 'admin';

    public const ACCOUNT_TYPE_MODERATOR = 'moderator';

    public const SUBSCRIPTION_PLAN_FREE = 'free';

    public const SUBSCRIPTION_PLAN_PLUS = 'plus';

    public const SUBSCRIPTION_PLAN_PRO = 'pro';

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_blocked' => 'boolean',
        ];
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->account_type === self::ACCOUNT_TYPE_ADMIN;
    }

    /**
     * Check if user is a moderator.
     */
    public function isModerator(): bool
    {
        return $this->account_type === self::ACCOUNT_TYPE_MODERATOR;
    }

    /**
     * Check if account is blocked.
     */
    public function isBlocked(): bool
    {
        return (bool) $this->is_blocked;
    }

    /**
     * Get the request logs for the user.
     */
    public function requestLogs(): HasMany
    {
        return $this->hasMany(RequestLog::class);
    }

    /**
     * Get saved user queries.
     */
    public function savedQueries(): HasMany
    {
        return $this->hasMany(UserSavedQuery::class);
    }

    /**
     * Get pinned modules for the user.
     */
    public function modulePins(): HasMany
    {
        return $this->hasMany(UserModulePin::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(UserSubscription::class)
            ->where('status', UserSubscription::STATUS_ACTIVE)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->latestOfMany('ends_at');
    }

    public function currentPlan(): AccountPlan
    {
        $subscription = $this->relationLoaded('activeSubscription')
            ? $this->activeSubscription
            : $this->activeSubscription()->first();

        return AccountPlan::fromNullable($subscription?->plan);
    }

    /**
     * Scope a query to only include admin users.
     */
    public function scopeAdmins($query)
    {
        return $query->where('account_type', self::ACCOUNT_TYPE_ADMIN);
    }

    /**
     * Scope a query to only include blocked users.
     */
    public function scopeBlocked($query)
    {
        return $query->where('is_blocked', true);
    }
}
