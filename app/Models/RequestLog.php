<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestLog extends Model
{
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
        'request_data',
        'response_data',
        'status_code',
        'response_time',
        'ip_address',
        'user_agent',
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
