<?php

namespace App\Services\Monitoring;

use App\Integrations\TelegramBot\MonitoringDelivery;
use App\Models\MonitoringReport;
use App\Services\Monitoring\Export\MonitoringWorkbook;
use App\Support\Http\DocumentResponseHeaders;
use App\Support\Reports\Contracts\ReportFilenamePolicyInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

final readonly class MonitoringExports
{
    public function __construct(private ReportFilenamePolicyInterface $filenames, private MonitoringDelivery $delivery) {}

    public function prepare(int $id, int $owner): void
    {
        $report = MonitoringReport::query()->with('user')->find($id);
        if ($report === null || $report->user_id !== $owner || ! $report->available() || $report->user->isBlocked() || ! $report->user->hasVerifiedEmail()) {
            return;
        }
        if ($report->file_status === 'ready') {
            $this->delivery->enqueue($report);

            return;
        }
        $token = (string) Str::uuid();
        $attempts = $report->attempts + ($report->lease_token !== null && $report->lease_until?->lte(now()) ? 1 : 0);
        if ($attempts >= config('monitoring.max_attempts')) {
            $report->update(['file_status' => 'failed', 'error' => 'worker_timeout', 'lease_token' => null, 'lease_until' => null]);

            return;
        }
        $claimed = MonitoringReport::query()->whereKey($id)->whereIn('file_status', ['pending', 'working'])
            ->where(fn ($q) => $q->whereNull('lease_until')->orWhere('lease_until', '<=', now()))
            ->update(['file_status' => 'working', 'attempts' => $attempts, 'lease_token' => $token, 'lease_until' => now()->addSeconds(config('monitoring.lease_seconds'))]);
        if (! $claimed) {
            return;
        }
        $report->refresh();
        $directory = 'monitoring/'.$owner.'/'.$report->project_id.'/'.$report->id;
        $disk = Storage::disk('private');
        $disk->makeDirectory($directory);
        $jsonPath = $directory.'/'.$token.'.json';
        $xlsxPath = $directory.'/'.$token.'.xlsx';
        try {
            $handle = fopen($disk->path($jsonPath), 'wb');
            if ($handle === false) {
                throw new SourceUnavailable('file_unavailable');
            }
            try {
                foreach ($this->chunks($report) as $chunk) {
                    if (fwrite($handle, $chunk) !== strlen($chunk)) {
                        throw new SourceUnavailable('file_write_failed');
                    }
                }
            } finally {
                fclose($handle);
            }
            if (! Excel::store(new MonitoringWorkbook($report), $xlsxPath, 'private')) {
                throw new SourceUnavailable('file_write_failed');
            }
            $ready = DB::transaction(function () use ($id, $owner, $token, $jsonPath, $xlsxPath): bool {
                $current = MonitoringReport::query()->lockForUpdate()->find($id);
                if ($current === null || $current->user_id !== $owner || ! $current->available() || $current->lease_token !== $token || $current->lease_until?->isPast()) {
                    return false;
                }
                $current->forceFill(['file_status' => 'ready', 'files' => ['json' => $jsonPath, 'xlsx' => $xlsxPath],
                    'lease_token' => null, 'lease_until' => null, 'dispatched_at' => null, 'error' => null])->save();

                return true;
            });
            if (! $ready) {
                $disk->delete([$jsonPath, $xlsxPath]);

                return;
            }
            $this->delivery->enqueue($report->fresh());
        } catch (Throwable) {
            $disk->delete([$jsonPath, $xlsxPath]);
            $attempts = $report->attempts + 1;
            MonitoringReport::query()->whereKey($id)->where('lease_token', $token)->update(['file_status' => $attempts >= config('monitoring.max_attempts') ? 'failed' : 'pending',
                'attempts' => $attempts, 'error' => 'export_failed', 'lease_token' => null, 'lease_until' => null, 'dispatched_at' => null, 'next_attempt_at' => now()->addMinutes(1)]);
        }
    }

    private function chunks(MonitoringReport $report): iterable
    {
        $encode = static fn ($value): string => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $metadata = ['schema' => 'free-search.monitoring.v1', 'report' => ['id' => $report->id, 'version' => $report->version, 'period' => $report->period,
            'start' => $report->start_at->toISOString(), 'end' => $report->end_at->toISOString(), 'timezone' => $report->timezone,
            'cutoff' => $report->cutoff_at->toISOString(), 'status' => $report->status, 'created_at' => $report->completed_at->toISOString()],
            'configuration' => $report->configuration, 'coverage' => $report->coverage, 'summary' => $report->summary];
        yield substr($encode($metadata), 0, -1).',"materials":[';
        $first = true;
        foreach ($report->items()->orderBy('id')->lazyById(200) as $item) {
            yield ($first ? '' : ',').$encode($item->snapshot);
            $first = false;
        }
        yield ']}';
    }

    public function artifact(MonitoringReport $report, string $format): array
    {
        abort_unless(in_array($format, ['json', 'xlsx'], true), 404);
        abort_unless($report->available(), 410);
        abort_unless($report->file_status === 'ready', 409);
        $path = $report->files[$format] ?? null;
        abort_unless(is_string($path) && Storage::disk('private')->exists($path), 410);

        return ['path' => Storage::disk('private')->path($path), 'name' => $this->filenames->buildWithExtension('monitoring',
            $report->id.'-v'.$report->version, $format, $report->completed_at)];
    }

    public function json(MonitoringReport $report): BinaryFileResponse
    {
        return $this->download($report, 'json');
    }

    public function excel(MonitoringReport $report): BinaryFileResponse
    {
        return $this->download($report, 'xlsx');
    }

    private function download(MonitoringReport $report, string $format): BinaryFileResponse
    {
        $artifact = $this->artifact($report, $format);

        return response()->download($artifact['path'], $artifact['name'], DocumentResponseHeaders::download());
    }
}
