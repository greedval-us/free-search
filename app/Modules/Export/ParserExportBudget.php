<?php

namespace App\Modules\Export;

use App\Exceptions\Public\ExternalServiceUnavailableException;
use App\Modules\Export\Excel\SheetDefinition;
use App\Modules\ParserSupport\ParserRunConfig;

final readonly class ParserExportBudget
{
    public function __construct(private ParserRunConfig $config) {}

    public function assertBytes(int $bytes): void
    {
        if ($bytes > $this->config->maxExportBytes()) {
            throw $this->exceeded();
        }
    }

    /** Guard before module builders duplicate source values into sheet arrays. */
    public function assertPayloadFits(array $payload): void
    {
        $bytes = 0;
        $this->countValues($payload, $bytes);
    }

    /** @param array<int, SheetDefinition> $definitions */
    public function assertSheetsFit(array $definitions): void
    {
        $cells = 0;
        $bytes = 0;
        foreach ($definitions as $definition) {
            $this->countRow($definition->headings, $cells, $bytes);
            foreach ($definition->rows as $row) {
                $this->countRow($row, $cells, $bytes);
            }
        }
    }

    private function countRow(array $row, int &$cells, int &$bytes): void
    {
        $cells += count($row);
        if ($cells > $this->config->maxExportCells()) {
            throw $this->exceeded();
        }
        $this->countValues($row, $bytes);
    }

    private function countValues(array $values, int &$bytes): void
    {
        foreach ($values as $key => $value) {
            $bytes += is_string($key) ? strlen($key) : 0;
            $this->assertBytes($bytes);
            if (is_array($value)) {
                $this->countValues($value, $bytes);
            } else {
                $bytes += strlen((string) $value);
                $this->assertBytes($bytes);
            }
        }
    }

    private function exceeded(): ExternalServiceUnavailableException
    {
        return new ExternalServiceUnavailableException(
            'errors.api.parser_run.export_limit_exceeded',
            'parser_export_limit_exceeded',
        );
    }
}
