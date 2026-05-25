<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    protected $signature = 'app:make-module {name : Module name, e.g. SiteIntel} {--force : Overwrite existing files}';

    protected $description = 'Create a module with standard layered architecture: Domain -> Application -> Infrastructure -> UI';

    public function __construct(
        private readonly Filesystem $files,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $moduleName = $this->normalizeModuleName((string) $this->argument('name'));

        if ($moduleName === '') {
            $this->error('Module name is required.');

            return self::FAILURE;
        }

        $modulePath = app_path('Modules/'.$moduleName);
        $force = (bool) $this->option('force');

        $this->createDirectories($modulePath);
        $this->createFiles($moduleName, $modulePath, $force);

        $this->newLine();
        $this->info("Module {$moduleName} created.");
        $this->line('Next steps:');
        $this->line('1. Register UI routes in routes/web.php');
        $this->line('2. Bind Domain contracts to Infrastructure implementations');
        $this->line('3. Wire frontend page/component to the new controller');

        return self::SUCCESS;
    }

    private function normalizeModuleName(string $name): string
    {
        $name = trim($name);
        $name = str_replace(['-', '_', ' '], '', $name);

        return Str::studly($name);
    }

    private function createDirectories(string $modulePath): void
    {
        $directories = [
            $modulePath.'/Domain/Contracts',
            $modulePath.'/Domain/Entities',
            $modulePath.'/Domain/ValueObjects',
            $modulePath.'/Domain/Services',
            $modulePath.'/Application/Contracts',
            $modulePath.'/Application/DTO',
            $modulePath.'/Application/UseCases',
            $modulePath.'/Infrastructure/Repositories',
            $modulePath.'/Infrastructure/Providers',
            $modulePath.'/Infrastructure/Clients',
            $modulePath.'/UI/Http/Controllers',
            $modulePath.'/UI/Http/Requests',
            $modulePath.'/UI/Transformers',
        ];

        foreach ($directories as $directory) {
            $this->files->ensureDirectoryExists($directory);
        }
    }

    private function createFiles(string $moduleName, string $modulePath, bool $force): void
    {
        $moduleSlug = Str::kebab($moduleName);
        $namespacePrefix = "App\\Modules\\{$moduleName}";

        $files = [
            "{$modulePath}/Domain/Contracts/{$moduleName}RepositoryInterface.php" => $this->domainContractStub($namespacePrefix, $moduleName),
            "{$modulePath}/Application/DTO/{$moduleName}ResultData.php" => $this->applicationDtoStub($namespacePrefix, $moduleName),
            "{$modulePath}/Application/UseCases/Run{$moduleName}UseCase.php" => $this->applicationUseCaseStub($namespacePrefix, $moduleName),
            "{$modulePath}/Infrastructure/Repositories/InMemory{$moduleName}Repository.php" => $this->infrastructureRepositoryStub($namespacePrefix, $moduleName),
            "{$modulePath}/Infrastructure/Providers/{$moduleName}ServiceProvider.php" => $this->infrastructureProviderStub($namespacePrefix, $moduleName),
            "{$modulePath}/UI/Http/Requests/{$moduleName}Request.php" => $this->uiRequestStub($namespacePrefix, $moduleName),
            "{$modulePath}/UI/Http/Controllers/{$moduleName}Controller.php" => $this->uiControllerStub($namespacePrefix, $moduleName),
            "{$modulePath}/UI/Transformers/{$moduleName}Resource.php" => $this->uiResourceStub($namespacePrefix, $moduleName),
            base_path("routes/web/{$moduleSlug}.php") => $this->routeStub($moduleName, $moduleSlug),
        ];

        foreach ($files as $path => $content) {
            $this->writeFile($path, $content, $force);
        }
    }

    private function writeFile(string $path, string $content, bool $force): void
    {
        if ($this->files->exists($path) && !$force) {
            $this->warn("Skipped existing file: {$path}");

            return;
        }

        $this->files->put($path, $content);
        $this->line("Created: {$path}");
    }

    private function domainContractStub(string $namespacePrefix, string $moduleName): string
    {
        return <<<PHP
<?php

namespace {$namespacePrefix}\Domain\Contracts;

interface {$moduleName}RepositoryInterface
{
    /**
     * @return array<string, mixed>
     */
    public function findByQuery(string \$query): array;
}

PHP;
    }

    private function applicationDtoStub(string $namespacePrefix, string $moduleName): string
    {
        return <<<PHP
<?php

namespace {$namespacePrefix}\Application\DTO;

class {$moduleName}ResultData
{
    /**
     * @param array<string, mixed> \$items
     */
    public function __construct(
        public readonly string \$query,
        public readonly array \$items,
    ) {
    }

    /**
     * @return array{query: string, items: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'query' => \$this->query,
            'items' => \$this->items,
        ];
    }
}

PHP;
    }

    private function applicationUseCaseStub(string $namespacePrefix, string $moduleName): string
    {
        return <<<PHP
<?php

namespace {$namespacePrefix}\Application\UseCases;

use {$namespacePrefix}\Application\DTO\\{$moduleName}ResultData;
use {$namespacePrefix}\Domain\Contracts\\{$moduleName}RepositoryInterface;

class Run{$moduleName}UseCase
{
    public function __construct(
        private readonly {$moduleName}RepositoryInterface \$repository,
    ) {
    }

    public function execute(string \$query): {$moduleName}ResultData
    {
        \$items = \$this->repository->findByQuery(\$query);

        return new {$moduleName}ResultData(
            query: \$query,
            items: \$items,
        );
    }
}

PHP;
    }

    private function infrastructureRepositoryStub(string $namespacePrefix, string $moduleName): string
    {
        return <<<PHP
<?php

namespace {$namespacePrefix}\Infrastructure\Repositories;

use {$namespacePrefix}\Domain\Contracts\\{$moduleName}RepositoryInterface;

class InMemory{$moduleName}Repository implements {$moduleName}RepositoryInterface
{
    public function findByQuery(string \$query): array
    {
        return [
            'source' => 'in_memory_stub',
            'query' => \$query,
            'results' => [],
        ];
    }
}

PHP;
    }

    private function infrastructureProviderStub(string $namespacePrefix, string $moduleName): string
    {
        return <<<PHP
<?php

namespace {$namespacePrefix}\Infrastructure\Providers;

use {$namespacePrefix}\Domain\Contracts\\{$moduleName}RepositoryInterface;
use {$namespacePrefix}\Infrastructure\Repositories\InMemory{$moduleName}Repository;
use Illuminate\Support\ServiceProvider;

class {$moduleName}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        \$this->app->bind(
            {$moduleName}RepositoryInterface::class,
            InMemory{$moduleName}Repository::class
        );
    }
}

PHP;
    }

    private function uiRequestStub(string $namespacePrefix, string $moduleName): string
    {
        return <<<PHP
<?php

namespace {$namespacePrefix}\UI\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class {$moduleName}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'max:255'],
        ];
    }
}

PHP;
    }

    private function uiControllerStub(string $namespacePrefix, string $moduleName): string
    {
        return <<<PHP
<?php

namespace {$namespacePrefix}\UI\Http\Controllers;

use {$namespacePrefix}\Application\UseCases\Run{$moduleName}UseCase;
use {$namespacePrefix}\UI\Http\Requests\\{$moduleName}Request;
use {$namespacePrefix}\UI\Transformers\\{$moduleName}Resource;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class {$moduleName}Controller extends Controller
{
    public function __invoke({$moduleName}Request \$request, Run{$moduleName}UseCase \$useCase): JsonResponse
    {
        \$data = \$useCase->execute((string) \$request->input('query'));

        return response()->json(new {$moduleName}Resource(\$data));
    }
}

PHP;
    }

    private function uiResourceStub(string $namespacePrefix, string $moduleName): string
    {
        return <<<PHP
<?php

namespace {$namespacePrefix}\UI\Transformers;

use {$namespacePrefix}\Application\DTO\\{$moduleName}ResultData;

class {$moduleName}Resource
{
    public function __construct(
        private readonly {$moduleName}ResultData \$data,
    ) {
    }

    /**
     * @return array{query: string, items: array<string, mixed>}
     */
    public function toArray(): array
    {
        return \$this->data->toArray();
    }
}

PHP;
    }

    private function routeStub(string $moduleName, string $moduleSlug): string
    {
        return <<<PHP
<?php

use App\Modules\\{$moduleName}\UI\Http\Controllers\\{$moduleName}Controller;
use Illuminate\Support\Facades\Route;

Route::post('{$moduleSlug}', {$moduleName}Controller::class)
    ->name('{$moduleSlug}.analyze');

PHP;
    }
}
