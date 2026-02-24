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
        'status',
        'tenant_id'
        
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
    // ---------------- CALCULATIONS ----------------
public function calculateExpectedCash(?int $tenantId = null)
    {
        // Base transactions query for this register
        $tx = $this->transactions()
            ->where('payment_method', 'Cash');

        // Apply tenant filter for non-super admins
        if ($tenantId) {
            $tx->where('tenant_id', $tenantId);
        }

        $cashSales = $tx->sum('total_amount');

        // Base cash movements query
        $cmQuery = $this->cashMovements();

        if ($tenantId) {
            $cmQuery->where('tenant_id', $tenantId);
        }

        $drops       = $cmQuery->where('type','drop')->sum('amount');
        $expenses    = $cmQuery->where('type','expense')->sum('amount');
        $payouts     = $cmQuery->where('type','payout')->sum('amount');
        $deposits    = $cmQuery->where('type','deposit')->sum('amount');
        $adjustments = $cmQuery->where('type','adjustment')->sum('amount');

        return $this->opening_cash + $cashSales + $deposits + $adjustments - $drops - $expenses - $payouts;
    }

    // ---------------- CASH MOVEMENTS SUMMARY ----------------
    public function cashMovementsSummary(?int $tenantId = null): array
    {
        $types = ['drop', 'expense', 'payout', 'deposit', 'adjustment'];
        $summary = [];

        foreach ($types as $type) {
            $query = $this->cashMovements()->where('type', $type);

            // ✅ Apply tenant filter if provided (for tenant admins)
            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }

            $summary[$type] = (float) $query->sum('amount');
        }

        return $summary;
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if(auth()->check() && empty($model->tenant_id)){
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });

        static::addGlobalScope('tenant', function ($query) {
            if(auth()->check()){
                $query->where('tenant_id', auth()->user()->tenant_id);
            }
        });
    }

}
