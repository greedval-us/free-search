<?php

namespace App\Modules\Export;

use Generator;
use JsonException;
use RuntimeException;
use Throwable;

final readonly class JsonExportEncoder
{
    private const FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;

    public function __construct(private ParserExportBudget $budget) {}

    /**
     * Prepare a bounded complete document before sending download headers.
     *
     * @return resource The caller owns the stream and must close it after sending.
     */
    public function prepare(array $payload)
    {
        $stream = tmpfile();
        if ($stream === false) {
            throw new RuntimeException('Unable to prepare the JSON export.');
        }

        try {
            $bytes = 0;
            foreach ($this->chunks($payload) as $chunk) {
                $bytes += strlen($chunk);
                $this->budget->assertBytes($bytes);
                $offset = 0;
                while ($offset < strlen($chunk)) {
                    $written = fwrite($stream, substr($chunk, $offset));
                    if ($written === false || $written === 0) {
                        throw new RuntimeException('Unable to write the JSON export.');
                    }
                    $offset += $written;
                }
            }
            if (! rewind($stream)) {
                throw new RuntimeException('Unable to rewind the JSON export.');
            }

            return $stream;
        } catch (Throwable $exception) {
            fclose($stream);

            throw $exception;
        }
    }

    /** @return Generator<string> */
    private function chunks(mixed $value, int $depth = 0): Generator
    {
        if (is_array($value) && $depth >= 512) {
            throw new JsonException('Maximum stack depth exceeded', JSON_ERROR_DEPTH);
        }
        if (is_string($value)) {
            yield from $this->stringChunks($value);

            return;
        }
        if (! is_array($value)) {
            $encoded = json_encode($value, self::FLAGS);
            yield str_replace("\n", "\n".str_repeat(' ', $depth * 4), $encoded);

            return;
        }
        if ($value === []) {
            yield '[]';

            return;
        }

        $list = array_is_list($value);
        yield $list ? '[' : '{';
        $first = true;
        foreach ($value as $key => $item) {
            yield ($first ? "\n" : ",\n").str_repeat(' ', ($depth + 1) * 4);
            $first = false;
            if (! $list) {
                yield from $this->stringChunks((string) $key);
                yield ': ';
            }
            yield from $this->chunks($item, $depth + 1);
        }
        yield "\n".str_repeat(' ', $depth * 4).($list ? ']' : '}');
    }

    /** @return Generator<string> */
    private function stringChunks(string $value): Generator
    {
        $this->budget->assertBytes(strlen($value));
        if (! mb_check_encoding($value, 'UTF-8')) {
            throw new JsonException('Malformed UTF-8 characters', JSON_ERROR_UTF8);
        }
        yield '"';
        for ($offset = 0; $offset < strlen($value); $offset += strlen($chunk)) {
            $chunk = mb_strcut($value, $offset, 8192, 'UTF-8');
            yield substr(json_encode($chunk, self::FLAGS), 1, -1);
        }
        yield '"';
    }
}
