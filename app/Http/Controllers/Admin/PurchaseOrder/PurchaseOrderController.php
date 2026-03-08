<?php

namespace App\Http\Controllers\Admin\PurchaseOrder;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        try {
            $purchaseOrders = PurchaseOrder::with('supplier', 'items.product')
                ->where('tenant_id', auth()->user()->tenant_id)
                ->orderByDesc('created_at')
                ->paginate(20);

            return view('admin.purchase_orders.index', compact('purchaseOrders'));
        } catch (\Throwable $e) {
            \Log::channel('pos')->error('Failed to load purchase orders', [
                'tenant_id' => auth()->user()->tenant_id ?? null,
                'user_id' => auth()->id() ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors('Unable to load purchase orders.');
        }
    }

    public function create()
    {
        $suppliers = Supplier::where('tenant_id', auth()->user()->tenant_id)->get();
        $products = Product::where('tenant_id', auth()->user()->tenant_id)->get();
        return view('admin.purchase_orders.create', compact('suppliers', 'products'));
    }

    public function store(Request $request)
    {
        try {
            $tenant = auth()->user()->tenant;

            $request->validate([
                'supplier_id' => 'required|exists:suppliers,id',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|numeric|min:1',
                'items.*.price' => 'required|numeric|min:0'
            ]);

            $poNumber = 'PO-' . time();

            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $poNumber,
                'supplier_id' => $request->supplier_id,
                'tenant_id' => $tenant->id,
                'status' => 'pending',
                'total_amount'=> 0
            ]);

            $totalAmount = 0;

            foreach ($request->items as $item) {
                $lineTotal = $item['quantity'] * $item['price'];

                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['price'],
                    'total_cost' => $lineTotal,
                    'tenant_id' => $tenant->id,
                    'received_quantity' => 0,
                ]);

                $totalAmount += $lineTotal;
            }

            $purchaseOrder->update(['total_amount' => $totalAmount]);

            return redirect()->route('purchase_orders.index')
                ->with('success', 'Purchase Order created successfully.');
        } catch (\Exception $e) {
            \Log::error('Failed to store purchase order', [
                'tenant_id' => auth()->user()->tenant_id,
                'user_id' => auth()->id(),
                'input' => $request->all(),
                'error' => $e->getMessage()
            ]);
            return back()->withErrors('Unable to create purchase order.')->withInput();
        }
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->authorizeTenant($purchaseOrder);
        $purchaseOrder->load('items.product', 'supplier');
        return view('admin.purchase_orders.show', compact('purchaseOrder'));
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        $this->authorizeTenant($purchaseOrder);
        $suppliers = Supplier::where('tenant_id', auth()->user()->tenant_id)->get();
        $products = Product::where('tenant_id', auth()->user()->tenant_id)->get();
        $purchaseOrder->load('items.product');
        return view('admin.purchase_orders.edit', compact('purchaseOrder', 'suppliers', 'products'));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
{
    $this->authorizeTenant($purchaseOrder);

    $request->validate([
        'supplier_id' => 'required|exists:suppliers,id',
        'items'       => 'required|array|min:1',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.quantity'   => 'required|integer|min:1',
        'items.*.price'      => 'required|numeric|min:0',
        'items.*.received_quantity' => 'nullable|integer|min:0',
    ]);

    DB::beginTransaction();
    try {

        $purchaseOrder->update([
            'supplier_id' => $request->supplier_id,
        ]);

        $existingItems = $purchaseOrder->items->keyBy('product_id');

        $purchaseOrder->items()->delete();

        $totalAmount = 0;

        foreach ($request->items as $item) {

            $oldReceived = $existingItems[$item['product_id']]->received_quantity ?? 0;
            $newReceived = $item['received_quantity'] ?? $oldReceived;

            $orderedQty = $item['quantity'];

            // Prevent illegal decrease
            if ($newReceived < $oldReceived) {
                throw new \Exception("Received quantity cannot be reduced during edit. Use adjustment.");
            }

            $diff = $newReceived - $oldReceived;

            if ($diff > 0) {

                $inventory = Inventory::firstOrNew([
                    'product_id' => $item['product_id'],
                    'tenant_id' => $purchaseOrder->tenant_id,
                ]);

                $inventory->quantity += $diff;
                $inventory->save();
            }

            $lineTotal = $item['quantity'] * $item['price'];

            PurchaseOrderItem::create([
                'purchase_order_id' => $purchaseOrder->id,
                'product_id'        => $item['product_id'],
                'quantity'          => $orderedQty,
                'received_quantity' => $newReceived,
                'unit_cost'         => $item['price'],
                'total_cost'        => $lineTotal,
                'tenant_id'         => $purchaseOrder->tenant_id,
            ]);

            $totalAmount += $lineTotal;
        }

        $purchaseOrder->update([
            'total_amount' => $totalAmount
        ]);

        DB::commit();

        return redirect()->route('purchase_orders.index')
            ->with('success', 'Purchase order updated successfully.');

    } catch (\Throwable $e) {

        DB::rollBack();

        \Log::channel('pos')->error('Failed to update purchase order', [
            'purchase_order_id' => $purchaseOrder->id,
            'error' => $e->getMessage()
        ]);

        return back()->withErrors($e->getMessage());
    }
}

    // SAFE DELETE: reverses received stock
    public function destroy(PurchaseOrder $purchaseOrder)
{
    $this->authorizeTenant($purchaseOrder);

    DB::beginTransaction();

    try {

        foreach ($purchaseOrder->items as $item) {

            if ($item->received_quantity > 0) {

                $inventory = Inventory::firstOrNew([
                    'product_id' => $item->product_id,
                    'tenant_id'  => $purchaseOrder->tenant_id
                ]);

                // Reverse stock
                $inventory->quantity -= $item->received_quantity;

                if ($inventory->quantity < 0) {
                    $inventory->quantity = 0;
                }

                $inventory->save();

                // Record stock movement
                \App\Models\StockMovement::create([
                    'product_id' => $item->product_id,
                    'user_id'    => auth()->id(),
                    'change'     => -$item->received_quantity,
                    'type'       => 'purchase_delete',
                    'reference'  => $purchaseOrder->po_number,
                    'tenant_id'  => $purchaseOrder->tenant_id
                ]);
            }
        }

        // Delete items then PO
        $purchaseOrder->items()->delete();
        $purchaseOrder->delete();

        DB::commit();

        return redirect()
            ->route('purchase_orders.index')
            ->with('success', 'Purchase order deleted and stock reversed.');

    } catch (\Throwable $e) {

        DB::rollBack();

        \Log::channel('pos')->error('Failed to delete purchase order', [
            'tenant_id' => auth()->user()->tenant_id ?? null,
            'user_id' => auth()->id() ?? null,
            'purchase_order_id' => $purchaseOrder->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return back()->withErrors('Unable to delete purchase order.');
    }
}

    // RECEIVE: only adds pending stock based on current received_quantity
    public function receive(PurchaseOrder $purchaseOrder)
{
    $this->authorizeTenant($purchaseOrder);

    if ($purchaseOrder->status === 'received') {
        return back()->withErrors('This purchase order has already been finalized.');
    }

    DB::beginTransaction();

    try {

        foreach ($purchaseOrder->items as $item) {

            $pendingQty = $item->quantity - $item->received_quantity;

            if ($pendingQty > 0) {

                $inventory = Inventory::firstOrNew([
                    'product_id' => $item->product_id,
                    'tenant_id' => $purchaseOrder->tenant_id
                ]);

                $inventory->quantity += $pendingQty;
                $inventory->save();

                // RECORD STOCK MOVEMENT
                $this->recordStockMovement(
                    $item->product_id,
                    $pendingQty,
                    'purchase_receive',
                    $purchaseOrder->po_number
                );

                $item->received_quantity += $pendingQty;
                $item->save();
            }
        }

        $purchaseOrder->status = 'received';
        $purchaseOrder->save();

        DB::commit();

        return back()->with('success','Purchase order received and inventory updated.');

    } catch (\Throwable $e) {

        DB::rollBack();

        return back()->withErrors('Unable to receive purchase order.');
    }
}
    // ADJUST RECEIVED: safe stock adjustment
  public function adjustReceived(Request $request, PurchaseOrder $purchaseOrder)
{
    $this->authorizeTenant($purchaseOrder);

    DB::beginTransaction();

    try {

        foreach ($purchaseOrder->items as $item) {

            $correctQty = $request->input('received_quantity.' . $item->id);
            $diff = $correctQty - $item->received_quantity;

            if ($diff != 0) {

                $inventory = Inventory::firstOrNew([
                    'product_id' => $item->product_id,
                    'tenant_id' => $purchaseOrder->tenant_id
                ]);

                $inventory->quantity += $diff;

                if ($inventory->quantity < 0) {
                    $inventory->quantity = 0;
                }

                $inventory->save();

                // RECORD MOVEMENT
                $this->recordStockMovement(
                    $item->product_id,
                    $diff,
                    'purchase_adjustment',
                    $purchaseOrder->po_number
                );

                $item->received_quantity = $correctQty;
                $item->save();
            }
        }

        DB::commit();

        return back()->with('success','Stock adjustment recorded.');

    } catch (\Throwable $e) {

        DB::rollBack();

        return back()->withErrors('Unable to adjust stock.');
    }
}

protected function recordStockMovement($productId, $qty, $type, $reference)
{
    \App\Models\StockMovement::create([
        'product_id' => $productId,
        'user_id' => auth()->id(),
        'change' => $qty,
        'type' => $type,
        'reference' => $reference,
        'tenant_id' => auth()->user()->tenant_id
    ]);
}

    protected function authorizeTenant(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'Unauthorized action for this tenant.');
        }
    }
}