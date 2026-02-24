<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'device_uuid',
        'action',
        'table_name',
        'record_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'tenant_id',
    ];

    protected static function booted()
    {
        // Auto-assign tenant_id when creating a new log
        static::creating(function ($model) {
            if(auth()->check() && empty($model->tenant_id)){
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });

        // Apply global tenant scope for non-superadmin users
        static::addGlobalScope('tenant', function ($query) {
            if(auth()->check() && auth()->user()->role !== 'superadmin'){
                $query->where('tenant_id', auth()->user()->tenant_id);
            }
        });
    }
}
