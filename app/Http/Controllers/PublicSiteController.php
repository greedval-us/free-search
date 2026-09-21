<?php

namespace App\Http\Controllers;

use App\Modules\Telegram\Tracking\TrackingConfig;
use App\Services\PublicSite\PublicFeatureCatalog;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

final class PublicSiteController extends Controller
{
    public function __construct(private readonly PublicFeatureCatalog $catalog) {}

    public function home(): Response
    {
        return Inertia::render('Welcome', $this->shared());
    }

    public function index(): Response
    {
        return Inertia::render('public/Features', $this->shared());
    }

    public function show(string $feature, TrackingConfig $tracking): Response
    {
        $page = collect($this->catalog->pages())->firstWhere('slug', $feature);
        abort_if($page === null, 404);

        return Inertia::render('public/Feature', [
            ...$this->shared(),
            'feature' => $page,
            'policy' => [
                'hours' => $tracking->interval(),
                'maxHours' => $tracking->integer('max_interval_hours'),
                'sources' => $tracking->integer('max_sources'),
                'months' => $tracking->integer('duration_months'),
                'days' => $tracking->integer('retention_days'),
                'parserDays' => (int) config('osint.parser_runs.retention_days'),
            ],
        ]);
    }

    private function shared(): array
    {
        return [
            'canRegister' => Features::enabled(Features::registration()),
            'features' => $this->catalog->pages(),
        ];
    }
}
