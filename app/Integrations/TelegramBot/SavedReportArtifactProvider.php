<?php

namespace App\Integrations\TelegramBot;

use App\Models\User;
use App\Modules\TelegramBot\Domain\Contracts\ScheduledReportArtifactProvider;
use App\Modules\TelegramBot\Domain\DTO\BotDocument;
use App\Modules\TelegramBot\Domain\Exceptions\ArtifactUnavailable;
use App\Modules\TelegramBot\Infrastructure\Artifacts\TemporaryDocuments;
use App\Modules\TelegramBot\Support\BotConfig;
use App\Support\Reports\SavedReportRenderer;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

abstract readonly class SavedReportArtifactProvider implements ScheduledReportArtifactProvider
{
    protected const FORMATS = ['html', 'json'];

    /** @return class-string<Model> */
    abstract protected function model(): string;

    abstract protected function filenamePrefix(): string;

    abstract protected function label(Model $report): string;

    abstract protected function view(Model $report): string;

    /** @return list<string> */
    abstract protected function listingColumns(): array;

    public function __construct(private BotConfig $config, private TemporaryDocuments $files, private SavedReportRenderer $renderer) {}

    final public function listing(int $userId, int $page): Paginator
    {
        return $this->query($userId)->with('schedule:id,timezone')->latest('id')
            ->simplePaginate($this->config->integer('page_size'), $this->listingColumns(), 'page', $page)
            ->through(fn (Model $report): array => ['id' => $report->id, 'label' => $this->label($report), 'formats' => self::FORMATS]);
    }

    final public function document(int $userId, int $id, string $format, string $locale): BotDocument
    {
        if (! in_array($format, self::FORMATS, true)) {
            throw new ArtifactUnavailable;
        }
        $report = $this->query($userId)->with('schedule:id,timezone')->find($id);
        if ($report === null) {
            throw new ArtifactUnavailable;
        }

        return $this->files->create($this->filenamePrefix().'-'.$id.'.'.$format, function (string $path) use ($report, $format, $locale): void {
            $content = $format === 'json'
                ? $this->renderer->json($report->data)
                : $this->renderer->html($this->view($report), $this->viewData($report), $locale);
            Storage::disk('local')->put($path, $content);
        });
    }

    final public function allows(User $user): bool
    {
        return ! $user->isBlocked() && $user->hasVerifiedEmail() && $this->moduleAllows($user);
    }

    final public function allowsDelivery(User $user, int $reportId, bool $automatic): bool
    {
        return $this->query($user->id, $user)->whereKey($reportId)
            ->when($automatic, fn (Builder $query) => $this->constrainAutomatic($query, $user->id))->exists();
    }

    final public function automaticRecipient(int $reportId): ?int
    {
        $model = $this->model();
        $userId = $model::query()->whereKey($reportId)->value('user_id');
        if ($userId === null) {
            return null;
        }
        $query = $this->query((int) $userId)->whereKey($reportId);

        return $this->constrainAutomatic($query, (int) $userId)->exists() ? (int) $userId : null;
    }

    protected function moduleAllows(User $user): bool
    {
        return true;
    }

    protected function constrainAccess(Builder $query, User $user): void {}

    /** @return array<string, mixed> */
    protected function viewData(Model $report): array
    {
        return ['report' => $report->data, 'generatedAt' => $report->completed_at?->setTimezone($this->timezone($report))->format('d.m.Y H:i')];
    }

    protected function timezone(Model $report): string
    {
        return $report->schedule?->timezone ?? config('app.timezone');
    }

    protected function periodLabel(Model $report): string
    {
        return $report->date_from?->setTimezone($this->timezone($report))->format('d.m')
            .'–'.$report->date_to?->setTimezone($this->timezone($report))->format('d.m.Y');
    }

    private function query(int $userId, ?User $user = null): Builder
    {
        $model = $this->model();
        $query = $model::query()->where('user_id', $userId)->where('status', $model::COMPLETED)->whereNotNull('data');
        $user ??= User::query()->find($userId);
        if ($user === null || ! $this->allows($user)) {
            $query->whereRaw('1 = 0');
        } else {
            $this->constrainAccess($query, $user);
        }

        return $query;
    }

    private function constrainAutomatic(Builder $query, int $userId): Builder
    {
        return $query->whereHas('schedule', fn (Builder $schedule) => $schedule
            ->where('user_id', $userId)->where('send_to_bot', true)->whereNull('deleted_at'))
            ->where(fn (Builder $report) => $report->where('is_manual', true)
                ->orWhereHas('schedule', fn (Builder $schedule) => $schedule->where('enabled', true)));
    }
}
