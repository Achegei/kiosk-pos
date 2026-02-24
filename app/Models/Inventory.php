<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quantity',
        'tenant_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

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
