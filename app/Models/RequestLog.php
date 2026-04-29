<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestLog extends Model
{
    public $timestamps = false;

    /**
     * The table associated with the model.
     */
    protected $table = 'request_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'method',
        'path',
        'route_name',
        'module_key',
        'action_key',
        'request_data',
        'query_preview',
        'response_data',
        'metadata',
        'status_code',
        'response_time',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'request_data' => 'array',
            'response_data' => 'array',
            'metadata' => 'array',
            'response_time' => 'double',
            'status_code' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the user that made the request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
