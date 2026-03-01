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
    $deviceUuid = $request->header('X-DEVICE-ID', 'unknown');

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
            try {
                if (empty($sale['items']) || !is_array($sale['items'])) {
                    \Log::info("Skipping sale with empty items", [
                        'tenant' => $tenantId,
                        'device_uuid' => $deviceUuid,
                        'sale_index' => $saleIndex
                    ]);
                    continue;
                }

                // ✅ Create tenant-safe transaction
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

                foreach ($sale['items'] as $itemIndex => $item) {
                    try {
                        if (!isset($item['product_id'], $item['quantity'], $item['price'])) {
                            \Log::warning("Skipping item with missing data", [
                                'tenant' => $tenantId,
                                'device_uuid' => $deviceUuid,
                                'sale_id' => $transaction->id,
                                'item_index' => $itemIndex
                            ]);
                            continue;
                        }

                        $productId = $item['product_id'];
                        $qty = (int)$item['quantity'];
                        $price = (float)$item['price'];
                        $lineTotal = $qty * $price;

                        // Tenant-safe inventory lock
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

                    } catch (\Throwable $e) {
                        \Log::error('Offline sale item processing failed', [
                            'tenant' => $tenantId,
                            'device_uuid' => $deviceUuid,
                            'sale_id' => $transaction->id ?? null,
                            'item_index' => $itemIndex,
                            'error' => $e->getMessage()
                        ]);

                        throw $e; // rollback entire transaction
                    }
                }

                $transaction->update(['total_amount' => $total]);

                // Save offline sale record
                $offline = OfflineSale::create([
                    'tenant_id' => $tenantId,
                    'sale_data' => $sale,
                    'synced' => true,
                    'device_uuid' => $deviceUuid,
                    'user_id' => $user->id,
                    'transaction_id' => $transaction->id,
                ]);

                $syncedIds[] = $offline->id;

            } catch (\Throwable $e) {
                \Log::error('Offline sale sync failed for a sale', [
                    'tenant' => $tenantId,
                    'device_uuid' => $deviceUuid,
                    'sale_index' => $saleIndex,
                    'error' => $e->getMessage()
                ]);

                throw $e; // rollback DB transaction
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