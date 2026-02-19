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
    public function index()
    {
        $transactions = Transaction::with('customer','items.product')->latest()->paginate(20);
        return view('transactions.index', compact('transactions'));
    }

    public function create()
    {
        $products = Product::with('inventory')->get();
        $customers = Customer::all();
        return view('transactions.create', compact('products','customers'));
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
     * POS CHECKOUT (SAFE INVENTORY)
     */
    public function posCheckout(Request $request)
    {
        $request->validate([
            'products'=>'required|array|min:1',
            'products.*.id'=>'required|exists:products,id',
            'products.*.quantity'=>'required|integer|min:1',
            'customer_id'=>'nullable|exists:customers,id',
            'payment_method'=>'required|in:Cash,Mpesa,Credit',
        ]);

        try {
            $receipt = null;

            DB::transaction(function() use ($request, &$receipt){
                $totalAmount = 0;
                $customerId = $request->customer_id;

                $transaction = Transaction::create([
                    'customer_id'=>$customerId,
                    'total_amount'=>0,
                    'payment_method'=>$request->payment_method,
                    'status'=>$request->payment_method==='Credit'?'On Credit':'Paid',
                ]);

                foreach($request->products as $item){
                    $product = Product::findOrFail($item['id']);
                    $qty = (int)$item['quantity'];

                    $inventory = Inventory::where('product_id',$product->id)
                        ->lockForUpdate()
                        ->first();

                    if(!$inventory){
                        throw new \Exception("Inventory missing for ".$product->name);
                    }

                    if($inventory->quantity < $qty){
                        throw new \Exception("Not enough stock for ".$product->name);
                    }

                    $lineTotal = $qty * $product->price;

                    TransactionItem::create([
                        'transaction_id'=>$transaction->id,
                        'product_id'=>$product->id,
                        'quantity'=>$qty,
                        'price'=>$product->price,
                        'total'=>$lineTotal,
                    ]);

                    $inventory->decrement('quantity', $qty);
                    $totalAmount += $lineTotal;
                }

                $transaction->update(['total_amount'=>$totalAmount]);

                if($request->payment_method==='Credit' && $customerId){
                    Customer::find($customerId)->increment('credit', $totalAmount);
                }

                // Build receipt
                $receipt = [
                    'id' => $transaction->id,
                    'total' => $totalAmount,
                    'items' => $transaction->items->map(fn($i) => [
                        'name' => $i->product->name,
                        'qty' => $i->quantity,
                        'price' => (float) $i->price
                    ])
                ];
            });

            return response()->json(['success'=>true, 'receipt'=>$receipt]);

        } catch (\Exception $e) {
            return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
        }
    }

    /**
     * DELETE TRANSACTION (RESTORE STOCK)
     */
    public function destroy(Transaction $transaction)
    {
        DB::transaction(function() use ($transaction){
            foreach($transaction->items as $item){
                $inventory = Inventory::where('product_id',$item->product_id)
                    ->lockForUpdate()
                    ->first();
                if($inventory){
                    $inventory->increment('quantity', $item->quantity);
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
}
