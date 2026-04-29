<?php

namespace App\Http\Controllers\EmailIntel;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmailIntel\EmailIntelLookupRequest;
use App\Modules\EmailIntel\Application\Services\EmailIntelService;
use Illuminate\Http\JsonResponse;

class EmailIntelController extends Controller
{
    public function __construct(
        private readonly EmailIntelService $emailIntelService,
    ) {
    }

    public function lookup(EmailIntelLookupRequest $request): JsonResponse
    {
        $this->applyRequestLocale($request->locale());

        return $this->jsonOk([
            'data' => $this->emailIntelService->lookup($request->email())->toArray(),
        ]);
    }
}
