<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashMovement extends Model
{
    protected $fillable = [
        'tenant_id',
        'register_session_id',
        'user_id',
        'type',
        'amount',
        'note'
    ];
}
