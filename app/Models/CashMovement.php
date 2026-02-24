<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashMovement extends Model
{
    protected $fillable = [
        'tenant_id',
        'register_session_id',
        'user_id',
        'type',
        'amount',
        'note'
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if(auth()->check() && empty($model->tenant_id)){
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });

        static::addGlobalScope('tenant', function ($query) {
        if(auth()->check()){
            $query->where('tenant_id', auth()->user()->tenant_id);
        }
    });
    
    }

}
