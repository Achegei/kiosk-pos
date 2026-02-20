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
        'is_active'
    ];

    // Cast timestamps to Carbon instances
    protected $casts = [
        'license_expires_at' => 'datetime',
    ];
}
