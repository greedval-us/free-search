<?php

namespace Tests\Feature\TelegramBot;

use App\Models\ParserRun;
use App\Modules\TelegramBot\Domain\DTO\BotDocument;
use App\Modules\TelegramBot\Domain\Exceptions\ArtifactUnavailable;
use App\Modules\TelegramBot\Infrastructure\Artifacts\ParserArtifactProvider;
use App\Modules\TelegramBot\Infrastructure\Artifacts\ReportArtifactProvider;
use App\Modules\TelegramBot\Infrastructure\Artifacts\TemporaryDocuments;
use App\Modules\TelegramBot\Models\BotReport;
use App\Support\Reports\ReportSnapshotStore;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

final class ArtifactTest extends TelegramBotTestCase
{
    public function test_parser_json_and_xlsx_are_private_and_reuse_localized_workbook(): void
    {
        $link = $this->linkedUser();
        $this->completedRun($link->user_id);
        $run = ParserRun::query()->sole();
        $provider = app(ParserArtifactProvider::class);
        $files = app(TemporaryDocuments::class);
        $originalLocale = app()->getLocale();
        $json = $provider->document($link->user_id, $run->id, 'json', 'en');
        $this->assertIsArray(json_decode(file_get_contents($json->path), true, flags: JSON_THROW_ON_ERROR));
        $files->remove($json);
        $xlsx = $provider->document($link->user_id, $run->id, 'xlsx', 'ru');
        $workbook = IOFactory::load($xlsx->path);
        $this->assertGreaterThanOrEqual(1, $workbook->getSheetCount());
        $this->assertNotEmpty($workbook->getSheet(0)->getCell('A1')->getValue());
        $workbook->disconnectWorksheets();
        $files->remove($xlsx);
        $this->assertSame($originalLocale, app()->getLocale());
        $this->assertSame([], Storage::disk('local')->allFiles(config('telegram_bot.temporary_directory')));
    }

    public function test_foreign_expired_and_unsupported_parser_files_cannot_be_downloaded(): void
    {
        $link = $this->linkedUser();
        $this->completedRun($link->user_id);
        $run = ParserRun::query()->sole();
        $provider = app(ParserArtifactProvider::class);
        $this->assertCount(0, $provider->listing($link->user_id + 1, 1)->items());
        foreach ([[$link->user_id + 1, 'json'], [$link->user_id, '../env']] as [$userId, $format]) {
            try {
                $provider->document($userId, $run->id, $format, 'en');
                $this->fail('Unauthorized file must not be generated.');
            } catch (ArtifactUnavailable) {
                $this->addToAssertionCount(1);
            }
        }
        $run->update(['expires_at' => now()->subSecond()]);
        $this->expectException(ArtifactUnavailable::class);
        $provider->document($link->user_id, $run->id, 'json', 'en');
    }

    public function test_report_catalog_stores_metadata_and_downloads_only_existing_owned_snapshot(): void
    {
        $link = $this->linkedUser();
        $store = app(ReportSnapshotStore::class);
        $store->store($link->user_id, 'site-intel.seo-audit', ['target' => 'example.com'], []);
        $record = BotReport::query()->sole();
        $this->assertSame(['target' => 'example.com'], $record->parameters);
        $this->assertArrayNotHasKey('report', $record->getAttributes());
        $provider = app(ReportArtifactProvider::class);
        $document = $provider->document($link->user_id, $record->id, 'html', 'en');
        $this->assertStringContainsString('<html', file_get_contents($document->path));
        app(TemporaryDocuments::class)->remove($document);
        $this->assertCount(0, $provider->listing($link->user_id + 1, 1)->items());
        $this->expectException(ArtifactUnavailable::class);
        $provider->document($link->user_id + 1, $record->id, 'html', 'en');
    }

    public function test_expired_snapshot_is_not_recomputed_by_bot(): void
    {
        config()->set('access.report_snapshot_ttl_seconds', 1);
        $link = $this->linkedUser();
        app(ReportSnapshotStore::class)->store($link->user_id, 'site-intel.seo-audit', [], []);
        $record = BotReport::query()->sole();
        $this->travel(2)->seconds();
        $this->expectException(ArtifactUnavailable::class);
        app(ReportArtifactProvider::class)->document($link->user_id, $record->id, 'html', 'en');
    }

    public function test_oversized_files_are_removed_and_cleanup_cannot_remove_original_files(): void
    {
        config()->set('telegram_bot.max_document_bytes', 2);
        $files = app(TemporaryDocuments::class);
        Storage::disk('local')->put('original.json', '{}');
        $files->remove(new BotDocument(Storage::disk('local')->path('original.json'), 'original.json'));
        Storage::disk('local')->assertExists('original.json');
        try {
            $files->create('result.json', fn (string $path) => Storage::disk('local')->put($path, 'large'));
            $this->fail('Oversized document must be rejected.');
        } catch (ArtifactUnavailable $exception) {
            $this->assertSame('file_too_large', $exception->reason);
        }
        $this->assertSame([], Storage::disk('local')->allFiles(config('telegram_bot.temporary_directory')));
    }
}
