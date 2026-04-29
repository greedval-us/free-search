<?php

namespace App\Modules\EmailIntel\Application\Services\EmailIntel;

use App\Modules\EmailIntel\Application\Services\EmailIntelService;
use Throwable;

final class EmailBulkIntelService
{
    public const MAX_EMAILS = 25;

    public function __construct(
        private readonly EmailIntelService $emailIntelService,
    ) {
    }

    /**
     * @param array<int, string> $emails
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public function lookup(array $emails): array
    {
        $items = [];

        foreach (array_slice(array_values(array_unique($emails)), 0, self::MAX_EMAILS) as $email) {
            try {
                $result = $this->emailIntelService->lookup($email)->toArray();
                $items[] = [
                    'email' => $email,
                    'ok' => true,
                    'riskScore' => $result['riskScore'],
                    'riskLevel' => $result['riskLevel'],
                    'provider' => $result['analytics']['provider']['name'] ?? '-',
                    'mxCount' => count($result['dns']['mx'] ?? []),
                    'hasSpf' => (bool) ($result['dns']['emailSecurity']['hasSpf'] ?? false),
                    'hasDmarc' => (bool) ($result['dns']['emailSecurity']['hasDmarc'] ?? false),
                    'deliverabilityScore' => (int) ($result['analytics']['deliverability']['score'] ?? 0),
                    'signals' => $result['signals'],
                ];
            } catch (Throwable $exception) {
                $items[] = [
                    'email' => $email,
                    'ok' => false,
                    'error' => $exception->getMessage(),
                ];
            }
        }

        return [
            'items' => $items,
            'total' => count($items),
        ];
    }
}
