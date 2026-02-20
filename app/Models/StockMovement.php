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
    ];
}
