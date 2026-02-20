<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransactionPayment extends Model
{
    use HasFactory;

    protected $table = 'transaction_payments';

    protected $fillable = [
        'transaction_id',
        'amount',
        'payment_method', // Cash, Mpesa, Credit
        'user_id',        // Staff who processed the payment
        'device_uuid',
        'note',           // Optional notes
    ];

    /**
     * Relationships
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
