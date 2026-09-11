<?php

namespace App\Modules\Export\Excel;

use Maatwebsite\Excel\Concerns\FromArray;

class StyledArraySheet extends StyledSheet implements FromArray
{
    public function array(): array
    {
        return $this->definition->rows;
    }
}
