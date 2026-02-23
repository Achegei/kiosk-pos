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
        'opened_at' => 'datetime',
        'closed_at' => 'datetime'
    ];

    // ---------------- RELATIONSHIPS ----------------
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'register_session_id');
    }

    public function cashMovements()
    {
        return $this->hasMany(CashMovement::class, 'register_session_id');
    }

    // ---------------- CALCULATIONS ----------------
    public function calculateExpectedCash()
    {
        $cashSales = $this->transactions()
            ->where('payment_method', 'Cash')
            ->sum('total_amount');

        $drops = $this->cashMovements()->where('type','drop')->sum('amount');
        $expenses = $this->cashMovements()->where('type','expense')->sum('amount');
        $payouts = $this->cashMovements()->where('type','payout')->sum('amount');
        $deposits = $this->cashMovements()->where('type','deposit')->sum('amount');
        $adjustments = $this->cashMovements()->where('type','adjustment')->sum('amount');

        return $this->opening_cash + $cashSales + $deposits + $adjustments - $drops - $expenses - $payouts;
    }

    public function cashMovementsSummary()
    {
        $types = ['drop', 'expense', 'payout', 'deposit', 'adjustment'];
        $summary = [];

        foreach ($types as $type) {
            $summary[$type] = $this->cashMovements()->where('type', $type)->sum('amount');
        }

        return $summary;
    }
}
