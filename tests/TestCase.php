<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\View\Compilers\Compiler;
use Illuminate\Support\Facades\Config;
use Laravel\Fortify\Features;
use ReflectionClass;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configureBladeCompiledPath();
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }

    protected function skipOnWindowsBladeLock(string $message = 'Skipped on Windows because Blade compiled views are file-lock sensitive in this environment.'): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped($message);
        }
    }

    private function configureBladeCompiledPath(): void
    {
        $compiledPath = storage_path(
            'framework/testing/views/' .
            str_replace('\\', '-', static::class) . '/' .
            str_replace([' ', '"', ':'], '-', $this->name())
        );

        if (! is_dir($compiledPath)) {
            mkdir($compiledPath, 0777, true);
        }

        Config::set('view.compiled', $compiledPath);

        $compiler = $this->app->make('blade.compiler');
        $reflection = new ReflectionClass(Compiler::class);
        $cachePath = $reflection->getProperty('cachePath');
        $cachePath->setAccessible(true);
        $cachePath->setValue($compiler, $compiledPath);
    }
}
