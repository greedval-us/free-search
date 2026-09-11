<?php

declare(strict_types=1);

namespace App\Http\Controllers\MoonShine;

use App\Http\Controllers\Controller;
use App\Http\Requests\MoonShine\TelegramSessionRequest;
use App\MoonShine\Pages\TelegramSessionsPage;
use App\Support\MadelineProto\Authentication\LoginStage;
use App\Support\MadelineProto\Authentication\SessionConnectionException;
use App\Support\MadelineProto\Authentication\SessionConnectionService;
use Illuminate\Http\RedirectResponse;
use Throwable;

final class TelegramSessionController extends Controller
{
    public function __invoke(TelegramSessionRequest $request, SessionConnectionService $service, ?int $connection = null): RedirectResponse
    {
        $id = $connection;
        $error = null;
        try {
            $actor = (int) auth('moonshine')->id();
            $result = match ($request->route()->getName()) {
                'moonshine.telegram-sessions.store' => $service->create($request->validated('name'), $actor),
                'moonshine.telegram-sessions.phone' => $service->submit($id, $actor, LoginStage::Phone, $request->validated('phone_number')),
                'moonshine.telegram-sessions.code' => $service->submit($id, $actor, LoginStage::Code, $request->validated('phone_code')),
                'moonshine.telegram-sessions.password' => $service->submit($id, $actor, LoginStage::Password, $request->validated('password')),
                default => $service->inspect($id, $actor),
            };
            $id = $result->id;
        } catch (SessionConnectionException $exception) {
            abort_if($exception->reason === 'not_owned', 403);
            $error = $exception->reason;
        } catch (Throwable) {
            $error = 'unavailable';
        }

        $url = app(TelegramSessionsPage::class)->getUrl();
        $response = redirect()->to($id === null ? $url : $url.'?connection='.$id);
        if ($error !== null) {
            $response->with('telegram_session_error', $error);
        }

        return $response->header('Cache-Control', 'no-store, private');
    }
}
