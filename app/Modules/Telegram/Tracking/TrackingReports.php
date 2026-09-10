<?php

namespace App\Modules\Telegram\Tracking;

use App\Models\TelegramTracking;
use App\Models\TelegramTrackingMessage;
use App\Modules\Telegram\Tracking\Export\TrackingWorkbook;
use Generator;
use Illuminate\Database\Eloquent\Builder;

final readonly class TrackingReports
{
    public const FORMATS = ['xlsx', 'json'];

    public function __construct(private TrackingConfig $config) {}

    public function available(int $userId): Builder
    {
        return TelegramTracking::query()->forUser($userId)->where(fn ($query) => $query
            ->where('purge_at', '>', now())->orWhere(fn ($query) => $query->whereNull('purge_at')
            ->where('expires_at', '>', now()->subDays($this->config->integer('retention_days')))));
    }

    public function workbook(TelegramTracking $task): TrackingWorkbook
    {
        return new TrackingWorkbook($task, (int) $task->messages()->max('id'));
    }

    /** JSON and Excel use a fixed upper ID so concurrent collection cannot expand a running export. */
    public function json(TelegramTracking $task): Generator
    {
        $lastId = (int) $task->messages()->max('id');
        $encode = static fn (mixed $data): string => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        yield '{"tracking":'.$encode(['id' => $task->id, 'name' => $task->name, 'mode' => $task->mode,
            'query' => $task->query, 'started_at' => $task->started_at, 'expires_at' => $task->expires_at,
            'generated_at' => now()->toIso8601String()]).',"messages":[';
        $first = true;
        foreach (TelegramTrackingMessage::query()->where('tracking_id', $task->id)->where('id', '<=', $lastId)->with('source')->lazyById() as $message) {
            yield ($first ? '' : ',').$encode(TrackingPresenter::message($message));
            $first = false;
        }
        yield ']}';
    }
}
