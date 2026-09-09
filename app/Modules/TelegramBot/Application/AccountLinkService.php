<?php

namespace App\Modules\TelegramBot\Application;

use App\Models\User;
use App\Modules\TelegramBot\Models\BotLink;
use App\Modules\TelegramBot\Models\LinkRequest;
use App\Modules\TelegramBot\Support\BotConfig;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final readonly class AccountLinkService
{
    public function __construct(private BotConfig $config) {}

    public function issue(User $user, string $locale): string
    {
        $this->requireActiveUser($user);
        $this->requireActiveBot();

        return DB::transaction(function () use ($user, $locale): string {
            $this->requireActiveUser(User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail());
            if (BotLink::query()->where('user_id', $user->id)->exists()) {
                $this->invalid('already_linked');
            }
            $token = Str::random(48);
            LinkRequest::query()->where('user_id', $user->id)->delete();
            LinkRequest::query()->create([
                'user_id' => $user->id,
                'token_hash' => hash('sha256', $token),
                'locale' => $this->config->locale($locale),
                'expires_at' => now()->addSeconds($this->config->integer('link_ttl_seconds')),
            ]);

            return 'https://t.me/'.$this->config->username().'?start='.$token;
        });
    }

    public function claim(string $token, string $telegramId, int $chatId): bool
    {
        if (! preg_match('/^[A-Za-z0-9]{48}$/D', $token)) {
            return false;
        }

        return DB::transaction(function () use ($token, $telegramId, $chatId): bool {
            $request = LinkRequest::query()->where('token_hash', hash('sha256', $token))
                ->where('expires_at', '>', now())->lockForUpdate()->first();
            if ($request === null || ($request->telegram_id !== null && $request->telegram_id !== $telegramId)) {
                return false;
            }
            $user = User::query()->find($request->user_id);
            if ($user === null || $user->isBlocked() || ! $user->hasVerifiedEmail() || ! $this->chatMatches($chatId, $telegramId)) {
                return false;
            }
            if (User::query()->where('telegram_id', $telegramId)->whereKeyNot($user->id)->exists()
                || BotLink::query()->where('telegram_id', $telegramId)->where('user_id', '!=', $user->id)->exists()) {
                return false;
            }
            $request->update(['telegram_id' => $telegramId, 'telegraph_chat_id' => $chatId]);

            return true;
        });
    }

    public function confirm(User $user, string $requestId): BotLink
    {
        $this->requireActiveUser($user);
        $this->requireActiveBot();

        try {
            return DB::transaction(function () use ($user, $requestId): BotLink {
                $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                $this->requireActiveUser($lockedUser);
                $request = LinkRequest::query()->where('user_id', $user->id)->whereKey($requestId)
                    ->where('expires_at', '>', now())->lockForUpdate()->first();
                if ($request === null || $request->telegram_id === null || ! $this->chatMatches((int) $request->telegraph_chat_id, $request->telegram_id)) {
                    $this->invalid('link_expired');
                }
                if (BotLink::query()->where('user_id', $user->id)->exists()
                    || User::query()->where('telegram_id', $request->telegram_id)->whereKeyNot($user->id)->exists()) {
                    $this->invalid('already_linked');
                }
                $lockedUser->forceFill(['telegram_id' => $request->telegram_id])->save();
                $link = BotLink::query()->create([
                    'user_id' => $user->id,
                    'telegram_id' => $request->telegram_id,
                    'telegraph_chat_id' => $request->telegraph_chat_id,
                    'locale' => $request->locale,
                ]);
                $request->delete();

                return $link;
            });
        } catch (UniqueConstraintViolationException) {
            $this->invalid('already_linked');
        }
    }

    public function disconnect(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $link = BotLink::query()->where('user_id', $user->id)->first();
            if ($link !== null) {
                if ($locked->telegram_id === $link->telegram_id) {
                    $locked->forceFill(['telegram_id' => null])->save();
                }
                $link->delete();
            }
            LinkRequest::query()->where('user_id', $user->id)->delete();
        });
    }

    private function chatMatches(int $chatId, string $telegramId): bool
    {
        return preg_match('/^[1-9][0-9]{0,15}$/D', $telegramId) === 1
            && TelegraphChat::query()->whereKey($chatId)->where('chat_id', $telegramId)
                ->where('telegraph_bot_id', $this->config->botId())->exists();
    }

    private function requireActiveUser(User $user): void
    {
        if ($user->isBlocked() || ! $user->hasVerifiedEmail()) {
            throw new AuthorizationException;
        }
    }

    private function requireActiveBot(): void
    {
        if (! $this->config->active()) {
            throw new ServiceUnavailableHttpException;
        }
    }

    private function invalid(string $key): never
    {
        throw ValidationException::withMessages(['telegram' => __('telegram_bot.errors.'.$key)]);
    }
}
