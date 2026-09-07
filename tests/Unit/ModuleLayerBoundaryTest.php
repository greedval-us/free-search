<?php

namespace Tests\Unit;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class ModuleLayerBoundaryTest extends TestCase
{
    private const SHARED_MODULES = ['Export', 'ParserSupport'];

    public function test_modules_do_not_depend_on_application_http_layer(): void
    {
        $violations = [];

        foreach ($this->modulePhpFiles() as $file) {
            $contents = file_get_contents($file->getPathname());

            if (is_string($contents) && str_contains($contents, 'App\\Http\\')) {
                $violations[] = $this->relativePath($file);
            }
        }

        $this->assertSame([], $violations, sprintf(
            'Modules must not depend on the application HTTP layer: %s',
            implode(', ', $violations),
        ));
    }

    public function test_modules_do_not_build_http_responses_with_global_helpers(): void
    {
        $violations = [];

        foreach ($this->modulePhpFiles() as $file) {
            $contents = file_get_contents($file->getPathname());

            if (is_string($contents)
                && preg_match('/\\b(?:abort|abort_if|abort_unless|redirect|response)\\s*\\(/', $contents) === 1
            ) {
                $violations[] = $this->relativePath($file);
            }
        }

        $this->assertSame([], $violations, sprintf(
            'Modules must return application results instead of HTTP responses: %s',
            implode(', ', $violations),
        ));
    }

    public function test_feature_modules_only_depend_on_shared_modules(): void
    {
        $violations = [];

        foreach ($this->modulePhpFiles() as $file) {
            $currentModule = $this->moduleName($file);
            $contents = file_get_contents($file->getPathname());

            if (! is_string($contents)) {
                continue;
            }

            foreach (preg_split('/\R/', $contents) ?: [] as $line) {
                $prefix = 'use App\\Modules\\';
                $line = trim($line);

                if (! str_starts_with($line, $prefix)) {
                    continue;
                }

                $importedModule = explode('\\', substr($line, strlen($prefix)), 2)[0];
                if ($importedModule !== $currentModule && ! in_array($importedModule, self::SHARED_MODULES, true)) {
                    $violations[] = sprintf('%s -> %s', $this->relativePath($file), $importedModule);
                }
            }
        }

        $this->assertSame([], array_values(array_unique($violations)), sprintf(
            'Feature modules may only depend on shared modules: %s',
            implode(', ', $violations),
        ));
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function modulePhpFiles(): iterable
    {
        $modulesPath = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Modules';
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($modulesPath, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                yield $file;
            }
        }
    }

    private function relativePath(SplFileInfo $file): string
    {
        return str_replace(
            dirname(__DIR__, 2).DIRECTORY_SEPARATOR,
            '',
            $file->getPathname(),
        );
    }

    private function moduleName(SplFileInfo $file): string
    {
        $modulesPath = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Modules'.DIRECTORY_SEPARATOR;
        $relativePath = str_replace($modulesPath, '', $file->getPathname());

        return explode(DIRECTORY_SEPARATOR, $relativePath, 2)[0];
    }
}
