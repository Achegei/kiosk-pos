<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Models\TransactionItem;
use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Http\Request;

class TransactionItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
        {
            try {
                $items = TransactionItem::with('product', 'transaction')
                    ->latest()
                    ->paginate(20);

                return view('transaction-items.index', compact('items'));

            } catch (\Throwable $e) {
                \Log::error('Failed to load transaction items', [
                    'user_id' => auth()->id(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return back()->withErrors('Unable to load transaction items. Please try again later.');
            }
        }

    /**
     * Show the form for creating a new resource.
     * Usually handled via TransactionController.
     */
    public function create()
        {
            try {
                $products = Product::where('tenant_id', auth()->user()->tenant_id)->get();
                return view('transaction-items.create', compact('products'));

            } catch (\Throwable $e) {
                \Log::error('Failed to load create transaction item page', [
                    'user_id' => auth()->id(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return back()->withErrors('Unable to open create transaction item page.');
            }
        }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
        {
            try {
                $request->validate([
                    'transaction_id' => 'required|exists:transactions,id',
                    'product_id' => 'required|exists:products,id',
                    'quantity' => 'required|integer|min:1',
                    'price' => 'required|numeric|min:0',
                ]);

                $productId = $request->product_id;
                $quantity = $request->quantity;
                $price = $request->price;
                $total = $quantity * $price;

                DB::transaction(function () use ($request, $productId, $quantity, $price, $total, &$item) {
                    $inventory = Inventory::where('product_id', $productId)
                        ->lockForUpdate()
                        ->first();

                    if (!$inventory || $inventory->quantity < $quantity) {
                        throw new \Exception('Not enough stock for this product.');
                    }

                    $inventory->decrement('quantity', $quantity);

                    $item = TransactionItem::create([
                        'transaction_id' => $request->transaction_id,
                        'product_id' => $productId,
                        'quantity' => $quantity,
                        'price' => $price,
                        'total' => $total,
                    ]);
                });

                return redirect()->back()->with('success', 'Transaction item added successfully!');

            } catch (\Illuminate\Validation\ValidationException $e) {
                return redirect()->back()->withErrors($e->errors());
            } catch (\Throwable $e) {
                \Log::error('Failed to store transaction item', [
                    'user_id' => auth()->id(),
                    'tenant_id' => auth()->user()->tenant_id,
                    'request' => $request->all(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return redirect()->back()->withErrors('Unable to add transaction item. ' . $e->getMessage());
            }
        }
    /**
     * Display the specified resource.
     */
    public function show(TransactionItem $transactionItem)
        {
            try {
                return view('transaction-items.show', compact('transactionItem'));

            } catch (\Throwable $e) {
                \Log::error('Failed to show transaction item', [
                    'user_id' => auth()->id(),
                    'transactionItem_id' => $transactionItem->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return back()->withErrors('Unable to display this transaction item.');
            }
        }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TransactionItem $transactionItem)
        {
            try {
                $products = Product::where('tenant_id', auth()->user()->tenant_id)->get();
                return view('transaction-items.edit', compact('transactionItem', 'products'));

            } catch (\Throwable $e) {
                \Log::error('Failed to load edit transaction item page', [
                    'user_id' => auth()->id(),
                    'transactionItem_id' => $transactionItem->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return back()->withErrors('Unable to open edit page.');
            }
        }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TransactionItem $transactionItem)
        {
            try {
                $request->validate([
                    'product_id' => 'required|exists:products,id',
                    'quantity' => 'required|integer|min:1',
                    'price' => 'required|numeric|min:0',
                ]);

                $newProductId = $request->product_id;
                $newQuantity = $request->quantity;
                $newPrice = $request->price;

                DB::transaction(function () use ($transactionItem, $newProductId, $newQuantity, $newPrice) {
                    if ($newProductId === $transactionItem->product_id) {
                        $diff = $newQuantity - $transactionItem->quantity;

                        $inventory = Inventory::where('product_id', $transactionItem->product_id)
                            ->lockForUpdate()
                            ->first();

                        if (!$inventory || $inventory->quantity < $diff) {
                            throw new \Exception('Not enough stock to increase quantity.');
                        }

                        $inventory->decrement('quantity', $diff);
                    } else {
                        $oldInventory = Inventory::where('product_id', $transactionItem->product_id)
                            ->lockForUpdate()
                            ->first();
                        if ($oldInventory) $oldInventory->increment('quantity', $transactionItem->quantity);

                        $newInventory = Inventory::where('product_id', $newProductId)
                            ->lockForUpdate()
                            ->first();
                        if (!$newInventory || $newInventory->quantity < $newQuantity) {
                            throw new \Exception('Not enough stock for the selected new product.');
                        }

                        $newInventory->decrement('quantity', $newQuantity);
                    }

                    $transactionItem->update([
                        'product_id' => $newProductId,
                        'quantity' => $newQuantity,
                        'price' => $newPrice,
                        'total' => $newQuantity * $newPrice,
                    ]);
                });

                return redirect()->back()->with('success', 'Transaction item updated successfully!');

            } catch (\Illuminate\Validation\ValidationException $e) {
                return redirect()->back()->withErrors($e->errors());
            } catch (\Throwable $e) {
                \Log::error('Failed to update transaction item', [
                    'user_id' => auth()->id(),
                    'tenant_id' => auth()->user()->tenant_id,
                    'transactionItem_id' => $transactionItem->id,
                    'request' => $request->all(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return redirect()->back()->withErrors('Unable to update transaction item. ' . $e->getMessage());
            }
        }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TransactionItem $transactionItem)
    {
        try {
            DB::transaction(function () use ($transactionItem) {
                $inventory = Inventory::where('product_id', $transactionItem->product_id)
                    ->lockForUpdate()
                    ->first();

                if ($inventory) {
                    $inventory->increment('quantity', $transactionItem->quantity);
                }

                $transactionItem->delete();
            });

            return redirect()->back()->with('success', 'Transaction item deleted successfully!');

        } catch (\Throwable $e) {
            \Log::error('Failed to delete transaction item', [
                'user_id' => auth()->id(),
                'tenant_id' => auth()->user()->tenant_id,
                'transactionItem_id' => $transactionItem->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->withErrors('Unable to delete transaction item. Please try again later.');
        }
    }
}