<?php

namespace Tests\Unit;

use App\Exceptions\Public\ExternalServiceUnavailableException;
use App\Modules\Export\JsonExportEncoder;
use App\Modules\Export\ParserExportBudget;
use App\Modules\ParserSupport\ParserRunConfig;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class JsonExportEncoderTest extends TestCase
{
    #[DataProvider('payloads')]
    public function test_prepared_document_preserves_the_existing_json_encode_bytes(array $payload): void
    {
        $encoder = $this->encoder(2000000);
        $expected = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $stream = $encoder->prepare($payload);
        $path = stream_get_meta_data($stream)['uri'];
        try {
            $this->assertSame($expected, stream_get_contents($stream));
        } finally {
            fclose($stream);
        }

        $this->assertFileDoesNotExist($path);
    }

    public static function payloads(): array
    {
        $deep = 'value';
        for ($depth = 0; $depth < 512; $depth++) {
            $deep = [$deep];
        }

        return [
            'nested partial collection' => [[
                'collection' => ['status' => 'failed', 'complete' => false],
                'rows' => [['text' => 'Привет / <script>', 'values' => [null, true, false, 1, -2, 2.5, 1.0]]],
                'empty' => [],
            ]],
            'sparse numeric keys' => [['rows' => [1 => 'first', 3 => 'third']]],
            'escaped values and property names' => [["a\"\n" => "\x01\t\r\n\\\"\u{2028}\u{2029}"]],
            'UTF-8 crossing chunk boundary' => [['text' => str_repeat('a', 8191).str_repeat('Я😀', 2000)]],
            'empty document' => [[]],
            'maximum supported array depth' => [$deep],
        ];
    }

    #[DataProvider('failedPayloads')]
    public function test_preparation_closes_its_temporary_stream_on_every_encoding_failure(array $payload, int $limit, string $exceptionType): void
    {
        $encoder = $this->encoder($limit);
        $streamsBefore = array_keys(get_resources('stream'));

        try {
            $encoder->prepare($payload);
            $this->fail('The document must be rejected before it becomes a download.');
        } catch (ExternalServiceUnavailableException|JsonException $exception) {
            $this->assertInstanceOf($exceptionType, $exception);
        }

        $this->assertSame($streamsBefore, array_keys(get_resources('stream')));
    }

    public static function failedPayloads(): array
    {
        return [
            'limit exceeded' => [['a' => 1], 13, ExternalServiceUnavailableException::class],
            'malformed UTF-8' => [['a' => "\xff"], 100, JsonException::class],
        ];
    }

    private function encoder(int $limit): JsonExportEncoder
    {
        return new JsonExportEncoder(new ParserExportBudget(ParserRunConfig::fromArray([
            'limits' => ['max_export_bytes' => $limit],
        ])));
    }
}
