<?php

namespace App\Support\MadelineProto;

use Closure;
use RuntimeException;

final class MadelineProtoRuntime
{
    public function executeFrom(string $directory, Closure $callback): mixed
    {
        $originalDirectory = getcwd();

        if ($originalDirectory === false) {
            throw new RuntimeException('Unable to resolve the current working directory.');
        }

        if (! chdir($directory)) {
            throw new RuntimeException("Unable to use MadelineProto runtime directory [{$directory}].");
        }

        try {
            return $callback();
        } finally {
            if (! chdir($originalDirectory)) {
                throw new RuntimeException("Unable to restore working directory [{$originalDirectory}].");
            }
        }
    }
}
