<?php

namespace Tests\Support;

use App\Models\TelegramTrackingSource;
use App\Modules\Telegram\Tracking\Contracts\TrackingGateway;
use App\Modules\Telegram\Tracking\TrackingException;
use Closure;

final class FakeTrackingGateway implements TrackingGateway
{
    public array $pages = [];

    public array $requests = [];

    public array $resolutions = [];

    public ?TrackingException $failure = null;

    public ?Closure $onResolve = null;

    public ?Closure $onHistory = null;

    public function resolve(array $groups, ?string $keyword = null): array
    {
        $this->resolutions[] = ['groups' => $groups, 'keyword' => $keyword];
        if ($this->onResolve !== null) {
            ($this->onResolve)();
        }
        if ($this->failure !== null) {
            throw $this->failure;
        }

        return array_map(fn ($group, $index) => [
            'session_name' => 'default', 'peer_id' => '-100'.($index + 12345), 'title' => $group, 'username' => $group,
        ], $groups, array_keys($groups));
    }

    public function fetch(TelegramTrackingSource $source): array
    {
        $this->requests[] = ['offset' => $source->offset_id, 'cursor' => $source->cursor_id,
            'window_start' => $source->window_start, 'window_end' => $source->window_end, 'method' => $source->collection_method];
        if ($this->onHistory !== null) {
            ($this->onHistory)();
        }
        if ($this->failure !== null) {
            throw $this->failure;
        }

        return array_shift($this->pages) ?? [];
    }
}
