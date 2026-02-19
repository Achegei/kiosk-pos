<?php

namespace App\Http\Controllers\Transactions;

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
        $items = TransactionItem::with('product', 'transaction')->latest()->paginate(20);
        return view('transaction-items.index', compact('items'));
    }

    /**
     * Show the form for creating a new resource.
     * (Usually handled via Transaction create)
     */
    public function create()
    {
        $products = Product::all();
        return view('transaction-items.create', compact('products'));
    }

    /**
     * Store a newly created resource in storage.
     * (Usually called from TransactionController)
     */
    public function store(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        $total = $request->quantity * $request->price;

        $item = TransactionItem::create([
            'transaction_id' => $request->transaction_id,
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
            'price' => $request->price,
            'total' => $total,
        ]);

        // Decrement inventory
        $inventory = Inventory::firstOrCreate(
            ['product_id' => $request->product_id],
            ['quantity' => 0]
        );
        $inventory->decrement('quantity', $request->quantity);

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
        $products = Product::all();
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

        // Adjust inventory first
        $diff = $request->quantity - $transactionItem->quantity;

        $inventory = Inventory::firstOrCreate(
            ['product_id' => $transactionItem->product_id],
            ['quantity' => 0]
        );
        $inventory->decrement('quantity', $diff);

        $transactionItem->update([
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
            'price' => $request->price,
            'total' => $request->quantity * $request->price,
        ]);

        return redirect()->back()->with('success', 'Transaction item updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TransactionItem $transactionItem)
    {
        // Refund inventory
        $inventory = Inventory::firstOrCreate(
            ['product_id' => $transactionItem->product_id],
            ['quantity' => 0]
        );
        $inventory->increment('quantity', $transactionItem->quantity);

        $transactionItem->delete();

        return redirect()->back()->with('success', 'Transaction item deleted successfully!');
    }
}
