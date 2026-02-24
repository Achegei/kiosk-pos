<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfflineSale extends Model
{
    protected $fillable = [
        'sale_data',   // JSON of items, total, payment method, etc.
        'synced',      // boolean flag
        'transaction_id', // link to created Transaction
        'tenant_id',   // for multi-tenancy
    ];

    protected $casts = [
        'sale_data' => 'array',
        'synced' => 'boolean',
    ];

    // Link to the Transaction once synced
    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }
    
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
