<?php

namespace App\Modules\TelegramBot\Models;

use Illuminate\Database\Eloquent\Model;

/** Legacy metadata retained only for scheduled cleanup after removing report delivery. */
final class BotReport extends Model
{
    protected $table = 'telegram_bot_reports';

    protected $guarded = ['id'];

    protected $hidden = ['parameters', 'fingerprint'];

    protected function casts(): array
    {
        return ['parameters' => 'array', 'expires_at' => 'immutable_datetime'];
    }
}
