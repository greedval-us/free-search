<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringReportItem extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['snapshot' => 'array'];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(MonitoringReport::class, 'report_id');
    }
}
