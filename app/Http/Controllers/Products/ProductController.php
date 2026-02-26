<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('inventory')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('products.index', compact('products'));
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            // unique per tenant
            'sku'   => 'required|string|max:100|unique:products,sku,NULL,id,tenant_id,' . auth()->user()->tenant_id,
            'price' => 'required|numeric|min:0',
        ]);

        $product = Product::create($request->only(['name','sku','price']));

        return redirect()->route('products.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        // ensure tenant owns this product
        $this->authorizeTenant($product);

        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $this->authorizeTenant($product);

        $request->validate([
            'name'  => 'required|string|max:255',
            // unique per tenant
            'sku'   => 'required|string|max:100|unique:products,sku,' . $product->id . ',id,tenant_id,' . auth()->user()->tenant_id,
            'price' => 'required|numeric|min:0',
        ]);

        $product->update($request->only(['name','sku','price']));

        return redirect()->route('products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $this->authorizeTenant($product);

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }

    public function search(Request $request)
    {
        $query = $request->query('query');
        if (!$query) return response()->json([]);

        $products = Product::with('inventory')
            ->where('is_active', 1)
            ->where(function($q) use ($query){
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('sku', 'LIKE', "%{$query}%");
            })
            ->take(10)
            ->get()
            ->map(fn($p) => [
                'id'    => $p->id,
                'name'  => $p->name,
                'price' => (float) $p->price,
                'stock' => optional($p->inventory)->quantity ?? 0,
            ]);

        return response()->json($products);
    }

    public function searchByBarcode($barcode)
    {
        $product = Product::with('inventory')
            ->where('sku', $barcode)
            ->where('is_active', 1)
            ->first();

        if (!$product) return response()->json([]);

        return response()->json([
            'id'    => $product->id,
            'name'  => $product->name,
            'price' => (float) $product->price,
            'stock' => optional($product->inventory)->quantity ?? 0,
        ]);
    }

    /**
     * Ensure product belongs to tenant
     */
    protected function authorizeTenant(Product $product)
    {
        if ($product->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'Unauthorized action for this tenant.');
        }
    }

}