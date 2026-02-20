<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OfflineSale;
use App\Models\Inventory;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class OfflineSaleController extends Controller
{
    /**
     * Sync offline sales from POS devices.
     */
    public function sync(Request $request)
    {
        $offlineSales = $request->input('sales');

        if (!is_array($offlineSales)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid payload. "sales" must be an array.'
            ], 400);
        }

        $deviceUuid = $request->header('X-DEVICE-ID', 'unknown');
        $userId = Auth::id() ?? null;

        DB::beginTransaction();

        try {
            $syncedIds = [];

            foreach ($offlineSales as $sale) {
                if (empty($sale['items']) || !is_array($sale['items'])) {
                    continue;
                }

                // Create Transaction record first
                $transaction = Transaction::create([
                    'customer_id' => $sale['customer_id'] ?? null,
                    'total_amount' => $sale['total'] ?? 0,
                    'payment_method' => $sale['payment_method'] ?? 'Cash',
                    'status' => $sale['payment_method'] === 'Credit' ? 'On Credit' : 'Paid',
                    'mpesa_code' => $sale['mpesa_code'] ?? null,
                ]);

                $total = 0;

                foreach ($sale['items'] as $item) {
                    if (!isset($item['product_id'], $item['quantity'], $item['price'])) continue;

                    $productId = $item['product_id'];
                    $qty = (int) $item['quantity'];
                    $price = (float) $item['price'];

                    $inventory = Inventory::where('product_id', $productId)
                        ->lockForUpdate()
                        ->first();

                    if (!$inventory) {
                        Log::warning('Offline sale sync: product not found', [
                            'product_id' => $productId,
                            'sale' => $sale
                        ]);
                        continue;
                    }

                    if ($inventory->quantity < $qty) {
                        throw new \Exception("Not enough stock for product ID {$productId}");
                    }

                    $lineTotal = $qty * $price;

                    TransactionItem::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $productId,
                        'quantity' => $qty,
                        'price' => $price,
                        'total' => $lineTotal,
                    ]);

                    $inventory->decrement('quantity', $qty);

                    // 🔹 Log stock movement
                    StockMovement::create([
                        'product_id' => $productId,
                        'user_id' => $userId,
                        'change' => -$qty,
                        'type' => 'sale',
                        'reference' => "Offline Transaction #{$transaction->id}",
                    ]);

                    $total += $lineTotal;
                }

                $transaction->update(['total_amount' => $total]);

                // Link offline sale to transaction and mark synced
                $offlineSale = OfflineSale::create([
                    'sale_data' => $sale,
                    'synced' => true,
                    'device_uuid' => $deviceUuid,
                    'user_id' => $userId,
                    'transaction_id' => $transaction->id,
                ]);

                $syncedIds[] = $offlineSale->id;
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Offline sales synced successfully',
                'synced_ids' => $syncedIds
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Offline sale sync failed', [
                'exception' => $e,
                'payload' => $offlineSales,
                'device_uuid' => $deviceUuid,
                'user_id' => $userId
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to sync offline sales: ' . $e->getMessage()
            ], 500);
        }
    }
}
