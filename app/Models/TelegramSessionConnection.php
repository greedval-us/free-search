<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\MadelineProto\Authentication\LoginStage;
use Illuminate\Database\Eloquent\Model;

final class TelegramSessionConnection extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_by' => 'integer',
            'stage' => LoginStage::class,
            'expires_at' => 'immutable_datetime',
            'retry_at' => 'immutable_datetime',
        ];
    }

    public function expired(): bool
    {
        return $this->stage !== LoginStage::Ready && $this->expires_at->isPast();
    }
}
