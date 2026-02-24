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
    public function sync(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated sync attempt'
            ], 401);
        }

        $tenantId = $user->tenant_id;
        $offlineSales = $request->input('sales');

        if (!is_array($offlineSales)) {
            return response()->json([
                'status' => 'error',
                'message' => '"sales" must be array'
            ], 400);
        }

        $deviceUuid = $request->header('X-DEVICE-ID', 'unknown');

        DB::beginTransaction();

        try {

            $syncedIds = [];

            foreach ($offlineSales as $sale) {

                if (empty($sale['items']) || !is_array($sale['items'])) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | CREATE TENANT-SAFE TRANSACTION
                |--------------------------------------------------------------------------
                */

                $transaction = Transaction::create([
                    'tenant_id' => $tenantId,
                    'customer_id' => $sale['customer_id'] ?? null,
                    'staff_id' => $user->id,
                    'total_amount' => 0,
                    'payment_method' => $sale['payment_method'] ?? 'Cash',
                    'status' => ($sale['payment_method'] ?? '') === 'Credit'
                        ? 'On Credit'
                        : 'Paid',
                    'mpesa_code' => $sale['mpesa_code'] ?? null,
                ]);

                $total = 0;

                foreach ($sale['items'] as $item) {

                    if (!isset($item['product_id'], $item['quantity'], $item['price'])) {
                        continue;
                    }

                    $productId = $item['product_id'];
                    $qty = (int)$item['quantity'];
                    $price = (float)$item['price'];

                    /*
                    |--------------------------------------------------------------------------
                    | TENANT SAFE INVENTORY LOCK
                    |--------------------------------------------------------------------------
                    */

                    $inventory = Inventory::where('tenant_id', $tenantId)
                        ->where('product_id', $productId)
                        ->lockForUpdate()
                        ->first();

                    if (!$inventory) {
                        throw new \Exception("Inventory missing for product {$productId}");
                    }

                    if ($inventory->quantity < $qty) {
                        throw new \Exception("Not enough stock for product {$productId}");
                    }

                    $lineTotal = $qty * $price;

                    TransactionItem::create([
                        'tenant_id' => $tenantId,
                        'transaction_id' => $transaction->id,
                        'product_id' => $productId,
                        'quantity' => $qty,
                        'price' => $price,
                        'total' => $lineTotal,
                    ]);

                    $inventory->decrement('quantity', $qty);

                    StockMovement::create([
                        'tenant_id' => $tenantId,
                        'product_id' => $productId,
                        'user_id' => $user->id,
                        'change' => -$qty,
                        'type' => 'sale',
                        'reference' => "Offline TX #{$transaction->id}",
                    ]);

                    $total += $lineTotal;
                }

                $transaction->update(['total_amount' => $total]);

                /*
                |--------------------------------------------------------------------------
                | SAVE OFFLINE SALE RECORD (TENANT SAFE)
                |--------------------------------------------------------------------------
                */

                $offline = OfflineSale::create([
                    'tenant_id' => $tenantId,
                    'sale_data' => $sale,
                    'synced' => true,
                    'device_uuid' => $deviceUuid,
                    'user_id' => $user->id,
                    'transaction_id' => $transaction->id,
                ]);

                $syncedIds[] = $offline->id;
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Offline sales synced',
                'synced_ids' => $syncedIds
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Offline sync failed', [
                'tenant' => $tenantId,
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Sync failed'
            ], 500);
        }
    }
}