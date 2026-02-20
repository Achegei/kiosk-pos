<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfflineSale extends Model
{
    protected $fillable = ['sale_data', 'synced'];
    protected $casts = [
        'sale_data' => 'array',
        'synced' => 'boolean',
    ];
}
