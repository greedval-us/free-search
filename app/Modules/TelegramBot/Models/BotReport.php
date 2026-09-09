<?php

namespace App\Modules\TelegramBot\Models;

use Illuminate\Database\Eloquent\Model;

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
