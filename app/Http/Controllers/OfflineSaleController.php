<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OfflineSale;
use App\Models\Inventory;
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

        // Validate input
        if (!is_array($offlineSales)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid payload. "sales" must be an array.'
            ], 400);
        }

        // Get device ID from header and authenticated user ID
        $deviceUuid = $request->header('X-DEVICE-ID', 'unknown');
        $userId = Auth::id() ?? null;

        DB::beginTransaction();

        try {
            $syncedIds = [];

            foreach ($offlineSales as $sale) {

                // Ensure items exist and are an array
                if (empty($sale['items']) || !is_array($sale['items'])) {
                    continue; // skip malformed sale
                }

                // Update inventory for each item
                foreach ($sale['items'] as $item) {
                    if (!isset($item['product_id'], $item['quantity'])) {
                        continue; // skip malformed item
                    }

                    $inventory = Inventory::where('product_id', $item['product_id'])->first();

                    if ($inventory) {
                        $inventory->quantity -= (int) $item['quantity'];
                        if ($inventory->quantity < 0) {
                            $inventory->quantity = 0; // prevent negative stock
                        }
                        $inventory->save();
                    } else {
                        // Log missing inventory for debugging
                        Log::warning('Offline sale sync: product not found', [
                            'product_id' => $item['product_id'],
                            'sale' => $sale
                        ]);
                    }
                }

                // Save offline sale record with device_uuid and user_id
                $offlineSale = OfflineSale::create([
                    'sale_data' => $sale,
                    'synced' => true,
                    'device_uuid' => $deviceUuid,
                    'user_id' => $userId,
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

            // Log the error for debugging
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
