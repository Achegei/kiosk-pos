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
            try {
                $lowStockThreshold = (int) setting('low_stock_threshold', 10);

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
            } catch (\Throwable $e) {
                \Log::channel('pos')->error('Failed to load inventories', [
                    'tenant_id' => auth()->user()->tenant_id ?? null,
                    'user_id' => auth()->id() ?? null,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return back()->withErrors('Unable to load inventories. Please try again later.');
            }
        }

public function create()
    {
        try {
            $products = Product::where('tenant_id', auth()->user()->tenant_id)
                ->whereDoesntHave('inventory')
                ->orderBy('name')
                ->get();

            return view('inventories.create', compact('products'));
        } catch (\Throwable $e) {
            \Log::channel('pos')->error('Failed to load products for inventory creation', [
                'tenant_id' => auth()->user()->tenant_id ?? null,
                'user_id' => auth()->id() ?? null,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors('Unable to load products. Please try again later.');
        }
    }

   public function store(Request $request)
        {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity'   => 'required|integer|min:0',
            ]);

            try {
                $inventory = Inventory::firstOrNew([
                    'product_id' => $request->product_id,
                    'tenant_id'  => auth()->user()->tenant_id,
                ]);

                $inventory->quantity = $request->quantity;
                $inventory->save();

                return redirect()->route('inventories.index')
                    ->with('success', 'Inventory saved successfully!');
            } catch (\Throwable $e) {
                \Log::channel('pos')->error('Failed to store inventory', [
                    'tenant_id' => auth()->user()->tenant_id ?? null,
                    'user_id' => auth()->id() ?? null,
                    'product_id' => $request->product_id,
                    'quantity' => $request->quantity,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return back()->withErrors('Unable to save inventory. Please try again.');
            }
        }

    public function edit(Inventory $inventory)
        {
            try {
                if ($inventory->tenant_id !== auth()->user()->tenant_id) {
                    abort(403, 'Unauthorized for this tenant.');
                }

                $products = Product::where('tenant_id', auth()->user()->tenant_id)
                    ->orderBy('name')
                    ->get();

                return view('inventories.edit', compact('inventory', 'products'));
            } catch (\Throwable $e) {
                \Log::channel('pos')->error('Failed to load inventory for edit', [
                    'tenant_id' => auth()->user()->tenant_id ?? null,
                    'user_id' => auth()->id() ?? null,
                    'inventory_id' => $inventory->id,
                    'error' => $e->getMessage(),
                ]);

                return back()->withErrors('Unable to edit inventory. Please try again.');
            }
        }

    public function update(Request $request, Inventory $inventory)
        {
            $request->validate([
                'quantity' => 'required|integer|min:0',
            ]);

            try {
                if ($inventory->tenant_id !== auth()->user()->tenant_id) {
                    abort(403, 'Unauthorized for this tenant.');
                }

                $inventory->update([
                    'quantity' => $request->quantity
                ]);

                return redirect()->route('inventories.index')
                    ->with('success', 'Inventory updated successfully!');
            } catch (\Throwable $e) {
                \Log::channel('pos')->error('Failed to update inventory', [
                    'tenant_id' => auth()->user()->tenant_id ?? null,
                    'user_id' => auth()->id() ?? null,
                    'inventory_id' => $inventory->id,
                    'quantity' => $request->quantity,
                    'error' => $e->getMessage(),
                ]);

                return back()->withErrors('Unable to update inventory. Please try again.');
            }
        }

    public function destroy(Inventory $inventory)
        {
            try {
                if ($inventory->tenant_id !== auth()->user()->tenant_id) {
                    abort(403, 'Unauthorized for this tenant.');
                }

                $inventory->delete();

                return redirect()->route('inventories.index')
                    ->with('success', 'Inventory removed successfully!');
            } catch (\Throwable $e) {
                \Log::channel('pos')->error('Failed to delete inventory', [
                    'tenant_id' => auth()->user()->tenant_id ?? null,
                    'user_id' => auth()->id() ?? null,
                    'inventory_id' => $inventory->id,
                    'error' => $e->getMessage(),
                ]);

                return back()->withErrors('Unable to delete inventory. Please try again.');
            }
        }

    public function toggle($id)
        {
            try {
                $inventory = Inventory::with('product')->findOrFail($id);

                if ($inventory->product && $inventory->product->tenant_id == auth()->user()->tenant_id) {
                    $inventory->product->is_active = !$inventory->product->is_active;
                    $inventory->product->save();
                } else {
                    abort(403, 'Unauthorized for this tenant.');
                }

                return back()->with('success', 'Product status updated.');
            } catch (\Throwable $e) {
                \Log::channel('pos')->error('Failed to toggle product status', [
                    'tenant_id' => auth()->user()->tenant_id ?? null,
                    'user_id' => auth()->id() ?? null,
                    'inventory_id' => $id,
                    'error' => $e->getMessage(),
                ]);

                return back()->withErrors('Unable to update product status.');
            }
        }
}