<?php

namespace App\Modules\TelegramBot\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class LinkRequest extends Model
{
    use HasUuids;

    protected $table = 'telegram_bot_link_requests';

    protected $guarded = ['id'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['expires_at' => 'immutable_datetime'];
    }
}
