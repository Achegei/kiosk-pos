<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
        {
            try {
                $products = Product::with('inventory')
                    ->where('tenant_id', auth()->user()->tenant_id) // only current tenant's products
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);

                return view('products.index', compact('products'));
            } catch (\Throwable $e) {
                \Log::channel('pos')->error('Failed to load products', [
                    'tenant_id' => auth()->user()->tenant_id ?? null,
                    'user_id'   => auth()->id() ?? null,
                    'error'     => $e->getMessage(),
                    'trace'     => $e->getTraceAsString(),
                ]);

                return back()->withErrors('Unable to load products. Please try again later.');
            }
        }

    public function create()
        {
            try {
                return view('products.create');
            } catch (\Throwable $e) {
                \Log::channel('pos')->error('Failed to show product creation form', [
                    'tenant_id' => auth()->user()->tenant_id ?? null,
                    'user_id'   => auth()->id() ?? null,
                    'error'     => $e->getMessage(),
                ]);

                return back()->withErrors('Unable to open form. Please try again later.');
            }
        }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'sku'   => 'required|string|max:100|unique:products,sku,NULL,id,tenant_id,' . auth()->user()->tenant_id,
            'price' => 'required|numeric|min:0',
        ]);

        try {
            $product = Product::create($request->only(['name','sku','price']));

            return redirect()->route('products.index')
                ->with('success', 'Product created successfully.');

        } catch (\Throwable $e) {
            \Log::channel('pos')->error('Product creation failed', [
                'tenant_id' => auth()->user()->tenant_id,
                'user_id'   => auth()->id(),
                'input'     => $request->all(),
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create product. Please try again.');
        }
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
            'sku'   => 'required|string|max:100|unique:products,sku,' . $product->id . ',id,tenant_id,' . auth()->user()->tenant_id,
            'price' => 'required|numeric|min:0',
        ]);

        try {
            $product->update($request->only(['name','sku','price']));

            return redirect()->route('products.index')
                ->with('success', 'Product updated successfully.');

        } catch (\Throwable $e) {
            \Log::channel('pos')->error('Product update failed', [
                'tenant_id' => auth()->user()->tenant_id,
                'user_id'   => auth()->id(),
                'product_id'=> $product->id,
                'input'     => $request->all(),
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update product. Please try again.');
        }
    }

    public function destroy(Product $product)
        {
            $this->authorizeTenant($product);

            try {
                $product->delete();

                return redirect()->route('products.index')
                    ->with('success', 'Product deleted successfully.');

            } catch (\Throwable $e) {
                \Log::channel('pos')->error('Product deletion failed', [
                    'tenant_id' => auth()->user()->tenant_id,
                    'user_id'   => auth()->id(),
                    'product_id'=> $product->id,
                    'error'     => $e->getMessage(),
                    'trace'     => $e->getTraceAsString(),
                ]);

                return redirect()->back()
                    ->with('error', 'Failed to delete product. Please try again.');
            }
        }

 public function search(Request $request)
        {
            try {
                $query = $request->query('query');
                if (!$query) return response()->json([]);

                $products = Product::with('inventory')
                    ->where('is_active', 1)
                    ->where('tenant_id', auth()->user()->tenant_id)
                    ->where(function($q) use ($query) {
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

            } catch (\Throwable $e) {
                \Log::channel('pos')->error('Product search failed', [
                    'tenant_id' => auth()->user()->tenant_id,
                    'user_id'   => auth()->id(),
                    'query'     => $request->query('query'),
                    'error'     => $e->getMessage(),
                    'trace'     => $e->getTraceAsString(),
                ]);

                return response()->json([], 500);
            }
        }
public function searchByBacode($barcode)
        {
            try {
                $product = Product::with('inventory')
                    ->where('sku', $barcode)
                    ->where('is_active', 1)
                    ->where('tenant_id', auth()->user()->tenant_id) // tenant scoping
                    ->first();

                if (!$product) {
                    return response()->json([]);
                }

                return response()->json([
                    'id'    => $product->id,
                    'name'  => $product->name,
                    'price' => (float) $product->price,
                    'stock' => optional($product->inventory)->quantity ?? 0,
                ]);

            } catch (\Throwable $e) {
                // Log the error to your POS log channel
                \Log::channel('pos')->error('Product lookup by barcode failed', [
                    'tenant_id' => auth()->user()->tenant_id ?? null,
                    'user_id'   => auth()->id() ?? null,
                    'barcode'   => $barcode,
                    'error'     => $e->getMessage(),
                    'trace'     => $e->getTraceAsString(),
                ]);

                // Return a 500 response for API clients
                return response()->json([], 500);
            }
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