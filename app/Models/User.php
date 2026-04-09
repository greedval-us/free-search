<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password', 'account_type', 'is_premium', 'premium_expires_at', 'telegram_id'])]
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
            'is_premium' => 'boolean',
            'premium_expires_at' => 'datetime',
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
     * Check if user has premium access.
     */
    public function hasPremiumAccess(): bool
    {
        if (!$this->is_premium) {
            return false;
        }

        if ($this->premium_expires_at === null) {
            return true;
        }

        return $this->premium_expires_at->isFuture();
    }

    /**
     * Check if premium has expired.
     */
    public function isPremiumExpired(): bool
    {
        if (!$this->is_premium) {
            return true;
        }

        if ($this->premium_expires_at === null) {
            return false;
        }

        return $this->premium_expires_at->isPast();
    }

    /**
     * Get the request logs for the user.
     */
    public function requestLogs(): HasMany
    {
        return $this->hasMany(RequestLog::class);
    }

    /**
     * Scope a query to only include admin users.
     */
    public function scopeAdmins($query)
    {
        return $query->where('account_type', self::ACCOUNT_TYPE_ADMIN);
    }

    /**
     * Scope a query to only include premium users.
     */
    public function scopePremium($query)
    {
        return $query->where('is_premium', true);
    }

    /**
     * Scope a query to only include active premium users.
     */
    public function scopeActivePremium($query)
    {
        return $query->where('is_premium', true)
            ->where(function ($q) {
                $q->whereNull('premium_expires_at')
                    ->orWhere('premium_expires_at', '>', now());
            });
    }
}
