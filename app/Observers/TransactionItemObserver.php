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

    /**
     * Centralized stock movement logger
     */
    protected function logMovement(TransactionItem $item, int $change, string $type): void
    {
        if ($change === 0) return; // don't log zero changes

        StockMovement::create([
            'product_id' => $item->product_id,
            'user_id' => Auth::id() ?? null,
            'change' => $change,
            'type' => $type,
            'reference' => 'Transaction #' . $item->transaction_id,
        ]);
    }
}
