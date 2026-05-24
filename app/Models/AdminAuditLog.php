<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAuditLog extends Model
{
    public $timestamps = false;

    protected $table = 'admin_audit_logs';

    /**
     * @var array<string>
     */
    protected $fillable = [
        'actor_admin_id',
        'actor_admin_name',
        'target_type',
        'target_id',
        'action',
        'changes',
        'meta',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'actor_admin_id' => 'integer',
            'target_id' => 'integer',
            'changes' => 'array',
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
