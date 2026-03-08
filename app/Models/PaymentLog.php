<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model
{
    // Specify the table (optional if Laravel can infer it)
    protected $table = 'payment_logs';

    // Mass-assignable fields
    protected $fillable = [
        'invoice_id',
        'api_ref',
        'status',
        'payload',
    ];

    // If your timestamps are nullable or default, Laravel handles them automatically
    public $timestamps = true;
}