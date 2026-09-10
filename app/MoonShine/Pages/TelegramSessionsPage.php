<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Models\TelegramSessionConnection;
use App\MoonShine\Support\AdminAccess;
use App\MoonShine\Support\AdminRole;
use App\Support\MadelineProto\MadelineProtoSessionPool;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Support\Attributes\Icon;
use MoonShine\UI\Components\FlexibleRender;

#[Icon('key')]
final class TelegramSessionsPage extends Page
{
    public function getTitle(): string
    {
        return __('admin_telegram_sessions.title');
    }

    protected function components(): iterable
    {
        $access = app(AdminAccess::class);
        $staff = auth('moonshine')->user();
        $staff = $staff instanceof MoonshineUser ? $staff : null;
        abort_unless($access->canViewResource($staff, self::class), 403);
        $canManage = $access->role($staff) === AdminRole::Admin
            && (! app()->isProduction() || request()->isSecure());
        $selected = null;
        if (request()->filled('connection')) {
            abort_unless($canManage, 403);
            $selected = TelegramSessionConnection::query()
                ->where('created_by', $staff->id)->findOrFail(request()->integer('connection'));
        }
        $pool = app(MadelineProtoSessionPool::class)->availableSessionNames();
        $managedNames = TelegramSessionConnection::query()->pluck('name')->all();
        $error = session('telegram_session_error') ?? $selected?->last_error;
        $errorMessages = __('admin_telegram_sessions.errors');

        return [FlexibleRender::make(view('moonshine.telegram-sessions.index'), [
            'connections' => TelegramSessionConnection::query()->latest('id')
                ->simplePaginate((int) config('madelineproto.admin_auth.page_size')),
            'legacyNames' => array_values(array_diff($pool, $managedNames)),
            'poolNames' => $pool,
            'selected' => $selected,
            'canManage' => $canManage,
            'actorId' => $staff->id,
            'pageUrl' => $this->getUrl(),
            'errorMessage' => $error === null ? null : ($errorMessages[$error] ?? $errorMessages['unavailable']),
        ])];
    }
}
