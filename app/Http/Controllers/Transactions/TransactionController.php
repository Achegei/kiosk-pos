<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{

    public function index()
    {
        $transactions = Transaction::with('customer','items.product')->latest()->paginate(20);
        return view('transactions.index', compact('transactions'));
    }


    public function create()
    {
        $products = Product::with('inventory')->get();   // ← IMPORTANT
        $customers = Customer::all();

        return view('transactions.create', compact('products','customers'));
    }



    /**
     * STORE TRANSACTION (SAFE INVENTORY VERSION)
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_id'=>'nullable|exists:customers,id',
            'products'=>'required|array',
            'products.*.id'=>'required|exists:products,id',
            'products.*.quantity'=>'required|integer|min:1',
            'payment_method'=>'required|in:Cash,Mpesa,Credit',
        ]);

        DB::transaction(function() use ($request){

            $totalAmount = 0;

            $transaction = Transaction::create([
                'customer_id'=>$request->customer_id,
                'total_amount'=>0,
                'payment_method'=>$request->payment_method,
                'status'=>$request->payment_method==='Credit'?'On Credit':'Paid',
            ]);


            foreach($request->products as $item){

                $product = Product::findOrFail($item['id']);
                $qty = (int)$item['quantity'];

                /**
                 * 🔴 LOCK INVENTORY ROW (CRITICAL FOR REAL POS)
                 */
                $inventory = Inventory::where('product_id',$product->id)
                                ->lockForUpdate()
                                ->first();

                if(!$inventory){
                    throw new \Exception("No inventory record for ".$product->name);
                }

                /**
                 * 🔴 PREVENT SELLING WITHOUT STOCK
                 */
                if($inventory->quantity < $qty){
                    throw new \Exception("Insufficient stock for ".$product->name." (Available ".$inventory->quantity.")");
                }

                /**
                 * CREATE ITEM
                 */
                $price = $product->price;
                $lineTotal = $qty * $price;

                TransactionItem::create([
                    'transaction_id'=>$transaction->id,
                    'product_id'=>$product->id,
                    'quantity'=>$qty,
                    'price'=>$price,
                    'total'=>$lineTotal,
                ]);

                /**
                 * 🔴 SAFE STOCK DEDUCTION
                 */
                $inventory->decrement('quantity',$qty);

                $totalAmount += $lineTotal;
            }

            $transaction->update(['total_amount'=>$totalAmount]);

            /**
             * CUSTOMER CREDIT
             */
            if($request->payment_method==='Credit' && $request->customer_id){
                Customer::find($request->customer_id)->increment('credit',$totalAmount);
            }

        });

        return redirect()->route('transactions.index')->with('success','Transaction recorded successfully!');
    }



    /**
     * DELETE TRANSACTION (RESTORE STOCK SAFE)
     */
    public function destroy(Transaction $transaction)
    {

        DB::transaction(function() use ($transaction){

            foreach($transaction->items as $item){

                $inventory = Inventory::where('product_id',$item->product_id)
                                ->lockForUpdate()
                                ->first();

                if($inventory){
                    $inventory->increment('quantity',$item->quantity);
                }

            }

            if($transaction->payment_method==='Credit' && $transaction->customer_id){
                Customer::find($transaction->customer_id)
                        ->decrement('credit',$transaction->total_amount);
            }

            $transaction->delete();

        });

        return redirect()->route('transactions.index')->with('success','Transaction deleted successfully!');
    }



    /**
     * POS PAGE
     */
    public function pos()
    {
        $customers = Customer::all();
        return view('transactions.pos', compact('customers'));
    }



    /**
     * POS CHECKOUT (ENTERPRISE SAFE VERSION)
     */
    public function posCheckout(Request $request)
    {

        $request->validate([
            'cart'=>'required|array|min:1',
            'cart.*.id'=>'required|exists:products,id',
            'cart.*.quantity'=>'required|integer|min:1',
            'customer_id'=>'nullable|exists:customers,id',
        ]);

        try{

            DB::transaction(function() use ($request){

                $totalAmount = 0;
                $customerId = $request->customer_id;

                $transaction = Transaction::create([
                    'customer_id'=>$customerId,
                    'total_amount'=>0,
                    'payment_method'=>$customerId?'Credit':'Cash',
                    'status'=>$customerId?'On Credit':'Paid',
                ]);

                foreach($request->cart as $item){

                    $product = Product::findOrFail($item['id']);
                    $qty = (int)$item['quantity'];

                    /**
                     * 🔴 LOCK INVENTORY
                     */
                    $inventory = Inventory::where('product_id',$product->id)
                                    ->lockForUpdate()
                                    ->first();

                    if(!$inventory){
                        throw new \Exception("Inventory missing for ".$product->name);
                    }

                    if($inventory->quantity < $qty){
                        throw new \Exception("Not enough stock for ".$product->name);
                    }

                    $price = $product->price;
                    $lineTotal = $qty * $price;

                    TransactionItem::create([
                        'transaction_id'=>$transaction->id,
                        'product_id'=>$product->id,
                        'quantity'=>$qty,
                        'price'=>$price,
                        'total'=>$lineTotal,
                    ]);

                    $inventory->decrement('quantity',$qty);

                    $totalAmount += $lineTotal;
                }

                $transaction->update(['total_amount'=>$totalAmount]);

                if($customerId){
                    Customer::find($customerId)->increment('credit',$totalAmount);
                }

            });

            return response()->json(['success'=>true]);

        }catch(\Exception $e){

            return response()->json([
                'success'=>false,
                'message'=>$e->getMessage()
            ],500);

        }

    }

    public function searchProduct(Request $request)
{
    $query = $request->get('query');

    $products = Product::with('inventory')
        ->where('name', 'LIKE', "%{$query}%")
        ->where('is_active', 1)
        ->take(10)
        ->get()
        ->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'price' => $p->price,
                'stock' => optional($p->inventory)->quantity ?? 0
            ];
        });

    return response()->json($products);
}

public function productByBarcode($barcode)
{
    $product = Product::with('inventory')
        ->where('sku', $barcode)   // IMPORTANT → using SKU as barcode
        ->where('is_active', 1)
        ->first();

    if(!$product){
        return response()->json(null);
    }

    return response()->json([
        'id' => $product->id,
        'name' => $product->name,
        'price' => $product->price,
        'stock' => optional($product->inventory)->quantity ?? 0
    ]);
}


}
