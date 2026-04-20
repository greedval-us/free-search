<?php

namespace App\Http\Controllers\Username;

use App\Http\Controllers\Controller;
use App\Http\Requests\Username\UsernameSearchRequest;
use App\Modules\Username\Application\Services\UsernameSearchService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class UsernameSearchController extends Controller
{
    public function __construct(
        private readonly UsernameSearchService $searchService,
    ) {
    }

    public function search(UsernameSearchRequest $request): JsonResponse
    {
        $result = $this->searchService->search($request->toQueryDTO());

        return response()->json([
            'ok' => true,
            ...$result->toArray(),
        ]);
    }

    public function report(UsernameSearchRequest $request): View|Response
    {
        app()->setLocale($request->locale());

        $result = $this->searchService->search($request->toQueryDTO());
        $data = $result->toArray();

        $viewData = [
            'report' => $data,
            'locale' => app()->getLocale(),
            'generatedAt' => Carbon::now(config('app.timezone'))->format('d.m.Y H:i'),
        ];

        if ($request->boolean('download')) {
            $filename = sprintf(
                'username-analytics-%s-%s.html',
                preg_replace('/[^a-z0-9_-]+/i', '-', $request->username()) ?: 'report',
                Carbon::now(config('app.timezone'))->format('Ymd-His')
            );

            return response()
                ->view('reports.username.analytics', $viewData)
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        }

        return view('reports.username.analytics', $viewData);
    }
}
