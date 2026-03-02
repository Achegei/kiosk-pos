<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class RegisterSession extends Model
{
    protected $fillable = [
        'user_id',
        'opening_cash',
        'closing_cash',
        'opened_at',
        'closed_at',
        'status',
        'tenant_id',
        'cash_sales',
        'mpesa_sales',
        'credit_sales',
        'difference',
        'cash_drops',
        'cash_expenses',
        'cash_payouts',
        'cash_deposits',
        'cash_adjustments',
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
    /**
     * Calculate expected cash in the register
     */
    public function calculateExpectedCash(?int $tenantId = null): float
    {
        // 1️⃣ Cash sales
        $cashSalesQuery = $this->transactions()->where('payment_method', 'Cash');
        if ($tenantId) {
            $cashSalesQuery->where('tenant_id', $tenantId);
        }
        $cashSales = (float) $cashSalesQuery->sum('total_amount');

        // 2️⃣ Cash movements (fresh query for each type)
        $movements = $this->cashMovementsSummary($tenantId);

        // 3️⃣ Compute expected cash
        return (float) (
            $this->opening_cash
            + $cashSales
            + $movements['deposit']
            + $movements['adjustment']
            - $movements['drop']
            - $movements['expense']
            - $movements['payout']
        );
    }

    // ---------------- CASH MOVEMENTS SUMMARY ----------------
    /**
     * Return sum of all cash movement types
     */
    public function cashMovementsSummary(): array
        {
            $movements = $this->cashMovements()
                ->get()
                ->groupBy('type')
                ->map(fn($items) => $items->sum('amount'));

            return [
                'drop'       => (float) $movements->get('drop', 0),
                'expense'    => (float) $movements->get('expense', 0),
                'payout'     => (float) $movements->get('payout', 0),
                'deposit'    => (float) $movements->get('deposit', 0),
                'adjustment' => (float) $movements->get('adjustment', 0),
            ];
        }

    // ---------------- MODEL BOOT ----------------
    protected static function booted()
    {
        // Automatically set tenant_id on creation
        static::creating(function ($model) {
            if (auth()->check() && empty($model->tenant_id)) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });

        // Global tenant scope
        static::addGlobalScope('tenant', function (Builder $query) {
            if (auth()->check()) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            }
        });
    }
}