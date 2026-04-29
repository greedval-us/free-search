<?php

namespace App\Support\Activity;

use Illuminate\Support\Facades\Schema;

class RequestLogSchemaInspector
{
    private ?bool $hasExtendedRequestLogSchema = null;

    public function hasExtendedSchema(): bool
    {
        if (is_bool($this->hasExtendedRequestLogSchema)) {
            return $this->hasExtendedRequestLogSchema;
        }

        if (!Schema::hasTable('request_logs')) {
            $this->hasExtendedRequestLogSchema = false;

            return false;
        }

        $this->hasExtendedRequestLogSchema = Schema::hasColumns('request_logs', [
            'module_key',
            'action_key',
            'query_preview',
            'metadata',
        ]);

        return $this->hasExtendedRequestLogSchema;
    }
}

