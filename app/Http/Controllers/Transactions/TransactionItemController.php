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
        $items = TransactionItem::with('product', 'transaction')
            ->latest()
            ->paginate(20);

        return view('transaction-items.index', compact('items'));
    }

    /**
     * Show the form for creating a new resource.
     * Usually handled via TransactionController.
     */
    public function create()
    {
        $products = Product::where('tenant_id', auth()->user()->tenant_id)->get();
        return view('transaction-items.create', compact('products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
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

        // Decrement inventory safely
        $inventory = Inventory::where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if (!$inventory || $inventory->quantity < $quantity) {
            return redirect()->back()->withErrors([
                'quantity' => 'Not enough stock for this product.'
            ]);
        }

        $inventory->decrement('quantity', $quantity);

        $item = TransactionItem::create([
            'transaction_id' => $request->transaction_id,
            'product_id' => $productId,
            'quantity' => $quantity,
            'price' => $price,
            'total' => $total,
        ]);

        return redirect()->back()->with('success', 'Transaction item added successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(TransactionItem $transactionItem)
    {
        return view('transaction-items.show', compact('transactionItem'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TransactionItem $transactionItem)
    {
        $products = Product::where('tenant_id', auth()->user()->tenant_id)->get();
        return view('transaction-items.edit', compact('transactionItem', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TransactionItem $transactionItem)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        $newProductId = $request->product_id;
        $newQuantity = $request->quantity;
        $newPrice = $request->price;

        // Handle inventory adjustments
        if ($newProductId === $transactionItem->product_id) {
            // Same product → adjust difference
            $diff = $newQuantity - $transactionItem->quantity;

            $inventory = Inventory::where('product_id', $transactionItem->product_id)
                ->lockForUpdate()
                ->first();

            if (!$inventory || $inventory->quantity < $diff) {
                return redirect()->back()->withErrors([
                    'quantity' => 'Not enough stock to increase quantity.'
                ]);
            }

            $inventory->decrement('quantity', $diff);
        } else {
            // Different product → refund old, deduct new
            $oldInventory = Inventory::where('product_id', $transactionItem->product_id)
                ->lockForUpdate()
                ->first();
            if ($oldInventory) $oldInventory->increment('quantity', $transactionItem->quantity);

            $newInventory = Inventory::where('product_id', $newProductId)
                ->lockForUpdate()
                ->first();
            if (!$newInventory || $newInventory->quantity < $newQuantity) {
                return redirect()->back()->withErrors([
                    'quantity' => 'Not enough stock for the selected new product.'
                ]);
            }
            $newInventory->decrement('quantity', $newQuantity);
        }

        // Update item
        $transactionItem->update([
            'product_id' => $newProductId,
            'quantity' => $newQuantity,
            'price' => $newPrice,
            'total' => $newQuantity * $newPrice,
        ]);

        return redirect()->back()->with('success', 'Transaction item updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TransactionItem $transactionItem)
    {
        // Refund inventory
        $inventory = Inventory::where('product_id', $transactionItem->product_id)
            ->lockForUpdate()
            ->first();

        if ($inventory) {
            $inventory->increment('quantity', $transactionItem->quantity);
        }

        $transactionItem->delete();

        return redirect()->back()->with('success', 'Transaction item deleted successfully!');
    }
}