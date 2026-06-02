<?php

namespace App\Http\Requests\Concerns;

trait NormalizesBooleanInputs
{
    /**
     * @param array<int, string> $keys
     */
    protected function normalizeBooleanInputs(array $keys): void
    {
        foreach ($keys as $key) {
            if (! $this->has($key)) {
                continue;
            }

            $value = $this->input($key);

            if (! is_string($value)) {
                continue;
            }

            $normalized = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

            if ($normalized === null) {
                continue;
            }

            $this->merge([
                $key => $normalized,
            ]);
        }
    }
}
