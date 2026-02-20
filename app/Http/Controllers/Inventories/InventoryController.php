<?php

namespace App\Http\Controllers\Inventories;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Http\Request;

class InventoryController extends Controller
{

    /**
     * Display inventory list + low stock alerts
     */
    public function index()
    {
        // Global threshold (fallback 10)
        $lowStockThreshold = (int) setting('low_stock_threshold', 10);

        // All inventory with product
        $inventories = Inventory::with('product')
            ->orderByDesc('updated_at')
            ->paginate(20);

        // Low stock
        $lowStock = Inventory::with('product')
            ->where('quantity', '<=', $lowStockThreshold)
            ->get();

        return view('inventories.index', compact(
            'inventories',
            'lowStock',
            'lowStockThreshold'
        ));
    }


    /**
     * Show create inventory form
     */
    public function create()
    {
        // Only show products WITHOUT inventory yet
        $products = Product::whereDoesntHave('inventory')->orderBy('name')->get();

        return view('inventories.create', compact('products'));
    }


    /**
     * Store inventory OR update existing safely
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:0',
        ]);

        // Prevent duplicate inventory rows
        $inventory = Inventory::firstOrNew([
            'product_id' => $request->product_id
        ]);

        $inventory->quantity = $request->quantity;
        $inventory->save();

        return redirect()
            ->route('inventories.index')
            ->with('success', 'Inventory saved successfully!');
    }


    /**
     * Edit inventory
     */
    public function edit(Inventory $inventory)
    {
        $products = Product::all();
        return view('inventories.edit', compact('inventory', 'products'));
    }


    /**
     * Update inventory quantity only
     */
    public function update(Request $request, Inventory $inventory)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $inventory->update([
            'quantity' => $request->quantity
        ]);

        return redirect()
            ->route('inventories.index')
            ->with('success', 'Inventory updated successfully!');
    }


    /**
     * Delete inventory
     */
    public function destroy(Inventory $inventory)
    {
        $inventory->delete();

        return redirect()
            ->route('inventories.index')
            ->with('success', 'Inventory removed successfully!');
    }


    /**
     * Toggle product active/inactive from inventory page
     */
    public function toggle($id)
    {
        $inventory = Inventory::with('product')->findOrFail($id);

        if ($inventory->product) {
            $inventory->product->is_active = !$inventory->product->is_active;
            $inventory->product->save();
        }

        return back()->with('success', 'Product status updated.');
    }

}
