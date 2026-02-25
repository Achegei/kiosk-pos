<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'subscription_status',
        'expiry_date'
    ];

     // Relationship to users (staff)
    public function staff()
    {
        return $this->hasMany(\App\Models\User::class, 'tenant_id')
                    ->whereIn('role', ['staff', 'supervisor', 'admin']);
    }

    public function transactions()
    {
        return $this->hasMany(\App\Models\Transaction::class, 'tenant_id');
    }
}