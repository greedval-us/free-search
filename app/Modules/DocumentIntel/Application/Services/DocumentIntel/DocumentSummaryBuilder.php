<?php

namespace App\Modules\DocumentIntel\Application\Services\DocumentIntel;

final class DocumentSummaryBuilder
{
    /**
     * @param array<int, array<string, mixed>> $documents
     * @param array<string, mixed> $riskOverview
     * @return array<string, mixed>
     */
    public function build(array $documents, array $riskOverview): array
    {
        $types = [];
        $withAuthor = 0;
        $withSoftware = 0;
        $withDates = 0;
        $failed = 0;

        foreach ($documents as $document) {
            $extension = (string) ($document['extension'] ?? '');
            if ($extension !== '') {
                $types[$extension] = ($types[$extension] ?? 0) + 1;
            }

            if (is_string($document['author'] ?? null) && trim((string) $document['author']) !== '') {
                $withAuthor++;
            }

            if (is_string($document['software'] ?? null) && trim((string) $document['software']) !== '') {
                $withSoftware++;
            }

            if (
                (is_string($document['createdAt'] ?? null) && trim((string) $document['createdAt']) !== '') ||
                (is_string($document['modifiedAt'] ?? null) && trim((string) $document['modifiedAt']) !== '')
            ) {
                $withDates++;
            }

            if (is_string($document['error'] ?? null) && trim((string) $document['error']) !== '') {
                $failed++;
            }
        }

        arsort($types);
        $typeRows = [];
        foreach ($types as $extension => $count) {
            $typeRows[] = [
                'extension' => $extension,
                'count' => $count,
            ];
        }

        return [
            'total' => count($documents),
            'failed' => $failed,
            'withAuthor' => $withAuthor,
            'withSoftware' => $withSoftware,
            'withDates' => $withDates,
            'types' => $typeRows,
            'risk' => $riskOverview,
        ];
    }
}
