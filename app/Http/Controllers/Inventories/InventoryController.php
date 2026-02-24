<?php

namespace App\Http\Controllers\Inventories;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index()
    {
        $lowStockThreshold = (int) setting('low_stock_threshold', 10);

        // tenant scope applied automatically
        $inventories = Inventory::with('product')
            ->orderByDesc('updated_at')
            ->paginate(20);

        $lowStock = Inventory::with('product')
            ->where('quantity', '<=', $lowStockThreshold)
            ->get();

        return view('inventories.index', compact(
            'inventories',
            'lowStock',
            'lowStockThreshold'
        ));
    }

    public function create()
    {
        // Only show products WITHOUT inventory for this tenant
        $products = Product::whereDoesntHave('inventory')->orderBy('name')->get();

        return view('inventories.create', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:0',
        ]);

        $inventory = Inventory::firstOrNew([
            'product_id' => $request->product_id,
            'tenant_id'  => auth()->user()->tenant_id, // tenant safety
        ]);

        $inventory->quantity = $request->quantity;
        $inventory->save();

        return redirect()
            ->route('inventories.index')
            ->with('success', 'Inventory saved successfully!');
    }

    public function edit(Inventory $inventory)
    {
        // Only show products for this tenant
        $products = Product::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('name')
            ->get();

        return view('inventories.edit', compact('inventory', 'products'));
    }

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

    public function destroy(Inventory $inventory)
    {
        $inventory->delete();

        return redirect()
            ->route('inventories.index')
            ->with('success', 'Inventory removed successfully!');
    }

    public function toggle($id)
    {
        $inventory = Inventory::with('product')->findOrFail($id);

        // ensure product belongs to tenant
        if ($inventory->product && $inventory->product->tenant_id == auth()->user()->tenant_id) {
            $inventory->product->is_active = !$inventory->product->is_active;
            $inventory->product->save();
        }

        return back()->with('success', 'Product status updated.');
    }
}