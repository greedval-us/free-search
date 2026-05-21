<?php

namespace App\Modules\YouTube\Parser;

use App\Modules\YouTube\Actions\Request\VideoCommentsAction;
use App\Modules\YouTube\DTO\Request\YouTubeCommentsQueryDTO;
use App\Modules\YouTube\Parser\Contracts\YouTubeParserApplicationServiceInterface;

class YouTubeParserApplicationService implements YouTubeParserApplicationServiceInterface
{
    public function __construct(private readonly VideoCommentsAction $videoCommentsAction) {}

    /**
     * @return array<string, mixed>
     */
    public function comments(YouTubeCommentsQueryDTO $query): array
    {
        return $this->videoCommentsAction->handle($query);
    }
}
