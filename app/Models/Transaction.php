<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'total_amount',
        'payment_method',
        'status',
        'register_session_id',   // ⭐ REQUIRED
        'user_id',               // ⭐ REQUIRED
        'mpesa_code'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function payments()
    {
        return $this->hasMany(TransactionPayment::class);
    }

    // ⭐ VERY IMPORTANT RELATION
    public function registerSession()
    {
        return $this->belongsTo(RegisterSession::class);
    }
}
