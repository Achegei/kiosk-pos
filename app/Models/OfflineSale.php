<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfflineSale extends Model
{
    protected $fillable = [
        'sale_data',   // JSON of items, total, payment method, etc.
        'synced',      // boolean flag
        'transaction_id', // link to created Transaction
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
}
