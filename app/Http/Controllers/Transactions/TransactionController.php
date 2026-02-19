<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Inventory;

class TransactionController extends Controller
{

    /* ========================================
       LIST TRANSACTIONS
    ======================================== */

    public function index()
    {
        $transactions = Transaction::with('customer','items.product')
            ->latest()
            ->paginate(20);

        $customers = Customer::all();
        return view('transactions.index', compact('transactions', 'customers')); // pass customers
    }


    /* ========================================
       CREATE PAGE
    ======================================== */

    public function create()
    {
        $products = Product::with('inventory')->get();
        $customers = Customer::all();

        return view('transactions.create', compact('products','customers'));
    }


    /* ========================================
       POS PAGE
    ======================================== */

    public function pos()
    {
        $customers = Customer::all();
        return view('transactions.pos', compact('customers'));
    }



    /* ========================================
       🔥 POS SEARCH (VERY IMPORTANT)
    ======================================== */

    public function searchProduct(Request $request)
    {
        $q = $request->query('query');

        if(!$q) return [];

        return Product::with('inventory')
            ->where('name','LIKE',"%{$q}%")
            ->limit(20)
            ->get()
            ->map(function($p){
                return [
                    'id'=>$p->id,
                    'name'=>$p->name,
                    'price'=>$p->price,
                    'sku'=>$p->sku,
                    'stock'=>$p->inventory->quantity ?? 0
                ];
            });
    }


    /* ========================================
       🔥 BARCODE SEARCH
    ======================================== */

    public function productByBarcode($barcode)
    {
        $p = Product::with('inventory')
            ->where('sku',$barcode)
            ->first();

        if(!$p) return response()->json(null);

        return [
            'id'=>$p->id,
            'name'=>$p->name,
            'price'=>$p->price,
            'sku'=>$p->sku,
            'stock'=>$p->inventory->quantity ?? 0
        ];
    }



    /* ========================================
       🔥 POS CHECKOUT (ULTRA SAFE VERSION)
    ======================================== */

    public function posCheckout(Request $request)
    {
        try {

            $request->validate([
                'products'=>'required|array|min:1',
                'products.*.id'=>'required|exists:products,id',
                'products.*.quantity'=>'required|integer|min:1',
                'customer_id'=>'nullable|exists:customers,id',
                'payment_method'=>'required|in:Cash,Mpesa,Credit',
                'mpesa_code'=>'nullable|string|max:10',
            ]);

            $receipt = null;

            DB::transaction(function() use ($request,&$receipt){

                $total = 0;

                if($request->payment_method==='Credit' && !$request->customer_id){
                    throw new \Exception("Credit sale requires customer");
                }

                $transaction = Transaction::create([
                    'customer_id'=>$request->customer_id,
                    'total_amount'=>0,
                    'payment_method'=>$request->payment_method,
                    'mpesa_code'=>$request->mpesa_code ?? null,
                    'status'=>$request->payment_method==='Credit' ? 'On Credit':'Paid'
                ]);


                foreach($request->products as $row){

                    $product = Product::findOrFail($row['id']);
                    $qty = (int)$row['quantity'];

                    $inventory = Inventory::where('product_id',$product->id)
                        ->lockForUpdate()
                        ->first();

                    if(!$inventory)
                        throw new \Exception("Inventory missing for ".$product->name);

                    if($inventory->quantity < $qty)
                        throw new \Exception("Not enough stock for ".$product->name);

                    $line = $qty * $product->price;

                    TransactionItem::create([
                        'transaction_id'=>$transaction->id,
                        'product_id'=>$product->id,
                        'quantity'=>$qty,
                        'price'=>$product->price,
                        'total'=>$line
                    ]);

                    $inventory->decrement('quantity',$qty);

                    $total += $line;
                }

                $transaction->update(['total_amount'=>$total]);

                if($request->payment_method==='Credit' && $request->customer_id){
                    Customer::find($request->customer_id)->increment('credit',$total);
                }

                $transaction->load('items.product');

                $receipt = [
                    'id'=>$transaction->id,
                    'total'=>$total,
                    'items'=>$transaction->items->map(fn($i)=>[
                        'name'=>$i->product->name,
                        'qty'=>$i->quantity,
                        'price'=>(float)$i->price,
                        'total'=>$i->quantity * $i->price
                    ])
                ];


            });

            return response()->json([
                'success'=>true,
                'receipt'=>$receipt
            ]);

        }
        catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'success'=>false,
                'message'=>$e->errors()
            ],422);
        }
        catch(\Throwable $e){

            \Log::error("POS ERROR ".$e->getMessage());

            return response()->json([
                'success'=>false,
                'message'=>$e->getMessage()
            ]);
        }
    }



    /* ========================================
       DELETE TRANSACTION (RESTORE STOCK)
    ======================================== */

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

        return redirect()
            ->route('transactions.index')
            ->with('success','Transaction deleted successfully!');
    }

}
