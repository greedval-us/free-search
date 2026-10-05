<?php

namespace App\Modules\Export\Excel;

final class CollectionSummaryRows
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<int, string>>
     */
    public static function fromPayload(array $payload): array
    {
        $collection = $payload['collection'] ?? null;
        if (! is_array($collection)) {
            return [];
        }

        $status = (string) ($collection['status'] ?? '');

        return [
            [(string) __('exports.common.collection_status'), (string) __('exports.common.collection_statuses.'.$status)],
            [(string) __('exports.common.collection_complete'), (string) __('exports.common.'.(! empty($collection['complete']) ? 'yes' : 'no'))],
        ];
    }
}
