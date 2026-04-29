<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\SaveQueryRequest;
use App\Models\User;
use App\Models\UserSavedQuery;
use App\Services\Dashboard\SavedQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SavedQueryController extends Controller
{
    public function __construct(
        private readonly SavedQueryService $savedQueryService,
    ) {
    }

    public function store(SaveQueryRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->savedQueryService->saveFromRequestLog($user, $request->requestLogId());

        return back();
    }

    public function destroy(Request $request, UserSavedQuery $savedQuery): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->savedQueryService->deleteForUser($user, $savedQuery);

        return back();
    }
}
