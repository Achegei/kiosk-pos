<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
   protected $fillable = [
    'purchase_order_id',
    'product_id',
    'quantity',
    'unit_cost',
    'total_cost',
    'tenant_id',
    'received_quantity'
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}