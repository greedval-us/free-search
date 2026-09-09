<?php

namespace App\Modules\TelegramBot\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BotDelivery extends Model
{
    public const PENDING = 'pending';

    public const SENT = 'sent';

    public const SKIPPED = 'skipped';

    public const FAILED = 'failed';

    protected $table = 'telegram_bot_deliveries';

    protected $guarded = ['id'];

    protected $hidden = ['payload'];

    protected $attributes = ['status' => self::PENDING, 'automatic' => true];

    protected function casts(): array
    {
        return ['payload' => 'array', 'automatic' => 'boolean', 'sent_at' => 'datetime', 'dispatched_at' => 'datetime'];
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(BotLink::class, 'link_id');
    }
}
