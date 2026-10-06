<?php

namespace App\Integrations\TelegramBot;

use App\Models\MonitoringProject;
use App\Models\MonitoringReport;
use App\Modules\TelegramBot\Application\Actions\MenuAction;
use App\Modules\TelegramBot\Application\BotAccess;
use App\Modules\TelegramBot\Domain\Contracts\BotAction;
use App\Modules\TelegramBot\Domain\DTO\BotButton;
use App\Modules\TelegramBot\Domain\DTO\BotContext;
use App\Modules\TelegramBot\Domain\DTO\BotScreen;
use App\Modules\TelegramBot\Models\BotDelivery;
use App\Modules\TelegramBot\Models\BotLink;
use App\Modules\TelegramBot\Support\BotConfig;
use App\Services\Monitoring\MonitoringManager;
use Illuminate\Support\Facades\DB;

final readonly class MonitoringAction implements BotAction
{
    public function __construct(private BotConfig $config, private BotAccess $access, private MenuAction $menu,
        private MonitoringDigestProvider $digests, private MonitoringManager $projects) {}

    public function key(): string
    {
        return 'monitor';
    }

    public function handle(BotContext $context, array $parameters): BotScreen
    {
        $link = $context->linkId === null ? null : BotLink::query()->with(['user', 'chat'])->find($context->linkId);
        if (! $this->access->allows($link) || (int) $link->user_id !== (int) $context->userId
            || $link->telegram_id !== $context->telegramId || (int) $link->telegraph_chat_id !== $context->chatId) {
            return $this->menu->handle(new BotContext($context->chatId, $context->telegramId, $context->locale, $context->requestId), []);
        }
        if (isset($parameters['r'])) {
            return $this->digests->screen(new BotDelivery(['kind' => 'monitoring_digest', 'reference' => (string) (int) $parameters['r']]), $link);
        }
        if (isset($parameters['i'])) {
            $project = MonitoringProject::query()->where('user_id', $link->user_id)->find((int) $parameters['i']);
            if ($project === null) {
                return new BotScreen(__('monitoring_bot.unavailable', [], $context->locale));
            }
            if (isset($parameters['d']) && in_array($parameters['d'], ['0', '1'], true)) {
                DB::transaction(function () use ($project, $parameters): void {
                    $current = MonitoringProject::query()->whereKey($project->id)->where('user_id', $project->user_id)->lockForUpdate()->first();
                    if ($current !== null && $current->status !== 'archived'
                        && (int) $current->generation === (int) ($parameters['g'] ?? 0)
                        && (bool) $current->delivery_enabled !== ($parameters['d'] === '1')) {
                        $this->projects->update($current, ['delivery_enabled' => $parameters['d'] === '1']);
                    }
                });
                $project->refresh();
            }

            return $this->project($project, $context);
        }
        $page = max(1, min($this->config->integer('max_page'), (int) ($parameters['page'] ?? 1)));
        $projects = MonitoringProject::query()->where('user_id', $link->user_id)->latest('id')
            ->simplePaginate($this->config->integer('page_size'), ['*'], 'page', $page);
        $buttons = [];
        foreach ($projects as $project) {
            $buttons[] = new BotButton(mb_substr($project->name, 0, 80), 'action', 'monitor', ['i' => $project->id]);
        }
        if ($page > 1) {
            $buttons[] = new BotButton(__('telegram_bot.menu.previous', [], $context->locale), 'action', 'monitor', ['page' => $page - 1]);
        }
        if ($projects->hasMorePages()) {
            $buttons[] = new BotButton(__('telegram_bot.menu.next', [], $context->locale), 'action', 'monitor', ['page' => $page + 1]);
        }
        $buttons[] = new BotButton(__('monitoring_bot.settings', [], $context->locale), 'url', $this->config->siteUrl('/monitoring'));
        $buttons[] = new BotButton(__('telegram_bot.menu.back', [], $context->locale), 'action', 'menu');

        return new BotScreen(__('monitoring_bot.'.($projects->isEmpty() ? 'no_projects' : 'projects'), [], $context->locale), $buttons);
    }

    private function project(MonitoringProject $project, BotContext $context): BotScreen
    {
        $buttons = [new BotButton(__('monitoring_bot.settings', [], $context->locale), 'url', $this->config->siteUrl('/monitoring/projects/'.$project->id))];
        $report = MonitoringReport::query()->where('user_id', $context->userId)->where('project_id', $project->id)
            ->whereIn('status', ['completed', 'partial', 'empty'])
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))->latest('id')->first();
        if ($report !== null) {
            $buttons[] = new BotButton(__('monitoring_bot.latest', [], $context->locale), 'action', 'monitor', ['r' => $report->id]);
        }
        if ($project->status !== 'archived') {
            $buttons[] = new BotButton(__('monitoring_bot.'.($project->delivery_enabled ? 'disable' : 'enable'), [], $context->locale), 'action', 'monitor',
                ['i' => $project->id, 'd' => $project->delivery_enabled ? '0' : '1', 'g' => $project->generation]);
        }
        $buttons[] = new BotButton(__('monitoring_bot.projects', [], $context->locale), 'action', 'monitor');

        return new BotScreen($project->name."\n\n".__('monitoring_bot.'.($project->delivery_enabled ? 'enabled' : 'disabled'), [], $context->locale)."\n".__('monitoring_bot.consent', [], $context->locale), $buttons);
    }
}
