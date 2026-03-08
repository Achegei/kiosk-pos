<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'payment_provider',
        'payment_reference',
        'amount',
        'status',    // Add this
        'payload',   // Add this
        'api_ref',   // Add this
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}