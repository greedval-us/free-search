<?php

namespace App\Modules\ParserSupport;

use RuntimeException;

final class ParserRunSourceRequestBudgetExceeded extends RuntimeException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('Parser source request budget exhausted or run unavailable.', 0, $previous);
    }
}
