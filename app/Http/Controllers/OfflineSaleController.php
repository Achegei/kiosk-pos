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
use App\Services\SaleProcessor;

class OfflineSaleController extends Controller
{
    public function sync(Request $request)
    {
        Log::info('DEVICE DEBUG', [
            'header' => $request->header('X-DEVICE-ID'),
            'input' => $request->input('device_uuid'),
        ]);
        $user = auth()->user();

        if (!$user) {
            \Log::warning('Unauthenticated sync attempt', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated sync attempt'
            ], 401);
        }

        $tenantId = $user->tenant_id;
        $offlineSales = $request->input('sales');
        $deviceUuid = $request->header('X-DEVICE-ID');

        if (!$deviceUuid) {
            $deviceUuid = $request->input('device_uuid');
        }

        if (!is_array($offlineSales)) {
            \Log::warning('Offline sync failed: sales not array', [
                'tenant' => $tenantId,
                'device_uuid' => $deviceUuid
            ]);

            return response()->json([
                'status' => 'error',
                'message' => '"sales" must be an array'
            ], 400);
        }

        DB::beginTransaction();

        try {

            $syncedIds = [];

            foreach ($offlineSales as $saleIndex => $sale) {
                // NEW (safe, does nothing yet)
                SaleProcessor::process($sale, $user, $deviceUuid);

                /*
                |--------------------------------------------------------------------------
                | 1. DUPLICATE PROTECTION (SAFE IDEMPOTENCY CHECK)
                |--------------------------------------------------------------------------
                */
                if (!empty($sale['local_id'])) {

                    $exists = OfflineSale::where('local_id', $sale['local_id'])
                        ->where('synced', true)
                        ->first();

                    if ($exists) {
                        \Log::info('Skipping already synced sale', [
                            'local_id' => $sale['local_id']
                        ]);
                        continue;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | 2. VALIDATION
                |--------------------------------------------------------------------------
                */
                if (empty($sale['items']) || !is_array($sale['items'])) {

                        \Log::warning("Invalid offline sale skipped", [
                            'tenant' => $tenantId,
                            'device_uuid' => $deviceUuid,
                            'sale_index' => $saleIndex,
                            'sale_payload' => $sale // 👈 IMPORTANT
                        ]);

                        continue;
                    }

                try {

                    /*
                    |--------------------------------------------------------------------------
                    | 3. CREATE TRANSACTION
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

                    /*
                    |--------------------------------------------------------------------------
                    | 4. PROCESS ITEMS
                    |--------------------------------------------------------------------------
                    */
                    foreach ($sale['items'] as $itemIndex => $item) {

                        if (!isset($item['product_id'], $item['quantity'], $item['price'])) {
                            \Log::warning("Skipping item with missing data", [
                                'tenant' => $tenantId,
                                'device_uuid' => $deviceUuid,
                                'sale_index' => $saleIndex,
                                'item_index' => $itemIndex
                            ]);
                            continue;
                        }

                        $productId = $item['product_id'];
                        $qty = (int) $item['quantity'];
                        $price = (float) $item['price'];
                        $lineTotal = $qty * $price;

                        /*
                        |--------------------------------------------------------------------------
                        | 5. INVENTORY LOCK (CRITICAL)
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

                        /*
                        |--------------------------------------------------------------------------
                        | 6. SAVE ITEM
                        |--------------------------------------------------------------------------
                        */
                        TransactionItem::create([
                            'tenant_id' => $tenantId,
                            'transaction_id' => $transaction->id,
                            'product_id' => $productId,
                            'quantity' => $qty,
                            'price' => $price,
                            'total' => $lineTotal,
                        ]);

                        Log::info('OFFLINE SALE CREATED', [
                                'transaction_id' => $transaction->id,
                                'device_uuid' => $deviceUuid,
                                'items_count' => count($sale['items'])
                            ]);
                                /*
                        |--------------------------------------------------------------------------
                        | 7. STOCK UPDATE
                        |--------------------------------------------------------------------------
                        */
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

                    /*
                    |--------------------------------------------------------------------------
                    | 8. FINALIZE TRANSACTION
                    |--------------------------------------------------------------------------
                    */
                    $transaction->update([
                        'total_amount' => $total
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | 9. SAVE OFFLINE LOG (IDEMPOTENCY KEY)
                    |--------------------------------------------------------------------------
                    */
                    $offline = OfflineSale::create([
                        'tenant_id' => $tenantId,
                        'sale_data' => $sale,
                        'synced' => true,
                        'device_uuid' => $deviceUuid,
                        'user_id' => $user->id,
                        'transaction_id' => $transaction->id,
                        'local_id' => $sale['local_id'] ?? null,
                    ]);

                    $syncedIds[] = $offline->id;

                } catch (\Throwable $e) {

                    \Log::error('Offline sale sync failed for a sale', [
                        'tenant' => $tenantId,
                        'device_uuid' => $deviceUuid,
                        'sale_index' => $saleIndex,
                        'error' => $e->getMessage()
                    ]);

                    throw $e; // rollback whole sync safely
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Offline sales synced',
                'synced_ids' => $syncedIds
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            \Log::error('Offline sales sync failed', [
                'tenant' => $tenantId,
                'device_uuid' => $deviceUuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Sync failed'
            ], 500);
        }
    }
}