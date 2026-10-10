<?php

namespace App\Modules\ParserSupport;

use RuntimeException;

final class ParserRunWriterLockBusyException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Parser run writer lock is busy.');
    }
}
