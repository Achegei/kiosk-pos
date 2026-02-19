<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::all();
        return view('products.index', compact('products'));
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
        ]);

        Product::create($request->all());
        return redirect()->route('products.index')->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku,' . $product->id,
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
        ]);

        $product->update($request->all());
        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }
    // App\Http\Controllers\Products\ProductController.php

    public function search(Request $request)
        {
            $query = $request->query('query');

            if (!$query) return response()->json([]);

            // Search by name or SKU (barcode), partial match
            $products = Product::where('name', 'LIKE', "%{$query}%")
                ->orWhere('sku', 'LIKE', "%{$query}%")
                ->take(10) // limit results for speed
                ->get();

            return response()->json($products);
        }
    public function searchByBarcode($barcode)
    {
        $product = Product::where('sku', $barcode)->first();

        return response()->json($product ? [$product] : []);
    }


}
