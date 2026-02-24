<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = [
        'device_uuid',
        'device_name',
        'shop_name',
        'license_expires_at',
        'is_active',
        'tenant_id'
    ];

    // Cast timestamps to Carbon instances
    protected $casts = [
        'license_expires_at' => 'datetime',
    ];

    protected static function booted()
    {
        // Auto-assign tenant
        static::creating(function ($model) {
            if(auth()->check() && empty($model->tenant_id)){
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });

        // Global tenant scope
        static::addGlobalScope('tenant', function ($query) {
            if(auth()->check()){
                $query->where('tenant_id', auth()->user()->tenant_id);
            }
        });
    }

}
