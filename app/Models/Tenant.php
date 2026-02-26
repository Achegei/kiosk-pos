<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'logo',
        'street_address',
        'building_name',
        'office_number',
        'subscription_status',
        'expiry_date'
    ];

    // Accessor for full logo URL
    public function getLogoUrlAttribute()
    {
        if ($this->logo) {
            // Assumes logos are stored in storage/app/public/tenant_logos
            return asset('storage/tenant_logos/' . $this->logo);
        }
        return null;
    }

    // Relationship to users (staff)
    public function staff()
    {
        return $this->hasMany(\App\Models\User::class, 'tenant_id')
                    ->whereIn('role', ['staff', 'supervisor', 'admin']);
    }

    // Relationship to transactions
    public function transactions()
    {
        return $this->hasMany(\App\Models\Transaction::class, 'tenant_id');
    }
}