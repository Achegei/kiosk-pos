<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    // ✅ Allow these fields to be mass-assigned
    protected $fillable = [
        'product_id',
        'user_id',
        'change',
        'type',
        'reference',
        'tenant_id',
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
