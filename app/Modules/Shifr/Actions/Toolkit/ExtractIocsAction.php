<?php

namespace App\Modules\Shifr\Actions\Toolkit;

use App\Modules\Shifr\DTO\Toolkit\IocLookupDTO;
use App\Modules\Shifr\DTO\Toolkit\Results\IocExtractResultDTO;

final class ExtractIocsAction
{
    public function execute(IocLookupDTO $dto): IocExtractResultDTO
    {
        $text = $dto->input;

        $emails = $this->unique('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $text);
        $urls = $this->unique('/https?:\/\/[^\s"\'\)\]>]+/i', $text);
        $domains = $this->unique('/\b(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}\b/i', $text);
        $ips = $this->unique('/\b(?:\d{1,3}\.){3}\d{1,3}\b/', $text);
        $telegramUsernames = $this->unique('/(?<!\w)@[a-zA-Z][a-zA-Z0-9_]{3,31}\b/', $text);

        return new IocExtractResultDTO(
            counts: [
                'emails' => count($emails),
                'urls' => count($urls),
                'domains' => count($domains),
                'ips' => count($ips),
                'telegramUsernames' => count($telegramUsernames),
            ],
            items: [
                'emails' => $emails,
                'urls' => $urls,
                'domains' => $domains,
                'ips' => $ips,
                'telegramUsernames' => $telegramUsernames,
            ],
        );
    }

    /**
     * @return array<int, string>
     */
    private function unique(string $pattern, string $text): array
    {
        preg_match_all($pattern, $text, $matches);
        $values = array_values(array_unique($matches[0] ?? []));

        sort($values);

        return $values;
    }
}
