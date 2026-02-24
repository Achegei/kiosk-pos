<?php

namespace App\Observers;

use App\Models\TransactionItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;

class TransactionItemObserver
{
    public function created(TransactionItem $item): void
    {
        $this->logMovement($item, -($item->quantity ?? 0), 'sale');
    }

    public function deleted(TransactionItem $item): void
    {
        $this->logMovement($item, ($item->quantity ?? 0), 'restock');
    }

    public function updated(TransactionItem $item): void
    {
        $originalQty = $item->getOriginal('quantity') ?? 0;
        $diff = ($item->quantity ?? 0) - $originalQty;

        if ($diff !== 0) {
            $type = $diff < 0 ? 'sale' : 'restock';
            $this->logMovement($item, $diff, $type);
        }
    }

    /**
     * Centralized stock movement logger
     */
    protected function logMovement(TransactionItem $item, int $change, string $type): void
    {
        if ($change === 0) return;

        StockMovement::create([
            'tenant_id' => Auth::user()->tenant_id ?? null, // tenant safety
            'product_id' => $item->product_id,
            'user_id' => Auth::id() ?? null,
            'device_uuid' => Auth::user()->device_uuid ?? null, // device-aware
            'change' => $change,
            'type' => $type,
            'reference' => 'Transaction #' . $item->transaction_id,
        ]);
    }
}