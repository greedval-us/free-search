<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ToggleModulePinRequest;
use App\Models\User;
use App\Services\Dashboard\ModulePinService;
use Illuminate\Http\RedirectResponse;

class ModulePinController extends Controller
{
    public function __construct(
        private readonly ModulePinService $modulePinService,
    ) {
    }

    public function toggle(ToggleModulePinRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->modulePinService->toggle($user, $request->moduleKey());

        return back();
    }
}
