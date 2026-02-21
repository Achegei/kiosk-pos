<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegisterSession extends Model
{
    protected $fillable = [
        'user_id',
        'opening_cash',
        'closing_cash',
        'opened_at',
        'closed_at',
        'status'
    ];

    protected $casts = [
        'opened_at'=>'datetime',
        'closed_at'=>'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'register_session_id');
    }
}
