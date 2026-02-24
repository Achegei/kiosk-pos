<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'price',
        'cost_price',
        'is_active',
        'tenant_id',
    ];

    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }

    protected static function booted()
    {
        // AUTO-ASSIGN TENANT
        static::creating(function ($model) {
            if (auth()->check() && empty($model->tenant_id)) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });

        // AUTO-CREATE INVENTORY
        static::created(function ($product) {
            Inventory::create([
                'product_id' => $product->id,
                'quantity' => 0,
            ]);
        });

        static::addGlobalScope('tenant', function ($query) {
            if(auth()->check()){
                $query->where('tenant_id', auth()->user()->tenant_id);
            }
        });
    }


}
