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
    ];

    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }

    protected static function booted()
    {
        static::created(function ($product) {
            Inventory::create([
                'product_id' => $product->id,
                'quantity' => 0, // default stock
            ]);
        });
    }

}
