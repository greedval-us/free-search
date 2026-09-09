<?php

namespace App\Modules\TelegramBot\Models;

use App\Models\User;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BotLink extends Model
{
    protected $table = 'telegram_bot_links';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['notifications_enabled' => 'boolean', 'exports_enabled' => 'boolean', 'broadcasts_enabled' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(TelegraphChat::class, 'telegraph_chat_id');
    }
}
