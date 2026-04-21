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
use App\Models\TransactionPayment;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    /* ============================
       LIST TRANSACTIONS
    ============================ */
    public function index()
        {
            try {
                $transactions = Transaction::with('customer','items.product')
                    ->latest()
                    ->paginate(20);

                $customers = Customer::where('tenant_id', auth()->user()->tenant_id)->get();

                return view('transactions.index', compact('transactions', 'customers'));

            } catch (\Throwable $e) {
                \Log::error('Failed to load transactions', [
                    'user_id' => auth()->id(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return back()->withErrors('Unable to load transactions. Please try again later.');
            }
        }

    /* ============================
       CREATE PAGE
    ============================ */
    public function create()
        {
            try {
                $products = Product::with('inventory')
                    ->where('tenant_id', auth()->user()->tenant_id)
                    ->get();

                $customers = Customer::where('tenant_id', auth()->user()->tenant_id)->get();

                return view('transactions.create', compact('products','customers'));

            } catch (\Throwable $e) {
                \Log::error('Failed to load transaction create page', [
                    'user_id' => auth()->id(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return back()->withErrors('Unable to open create transaction page.');
            }
        }

    /* ============================
       POS PAGE
    ============================ */
    public function pos()
    {
        try {
            $customers = Customer::where('tenant_id', auth()->user()->tenant_id)->get();

            // ✅ Get current tenant/store info
            $tenant = auth()->user()->tenant; // assuming User has tenant relationship
            $store = [
                'name' => $tenant->name,
                'address' => trim("{$tenant->building_name} {$tenant->street_address}"),
                'phone' => $tenant->phone ?? 'N/A',
                'footer' => $tenant->default_notes ?? 'Thank you for shopping with us!'
            ];

            // pass store info to view
            return view('transactions.pos', compact('customers', 'store'));

        } catch (\Throwable $e) {
            \Log::error('Failed to load POS page', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors('Unable to open POS page.');
        }
    }

    /* ============================
       POS SEARCH
    ============================ */
    public function searchProduct(Request $request)
        {
            try {
                $q = $request->query('query');
                if(!$q) return [];

                return Product::with('inventory')
                    ->where('tenant_id', auth()->user()->tenant_id)
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

            } catch (\Throwable $e) {
                \Log::error('Failed to search products', [
                    'user_id' => auth()->id(),
                    'query' => $request->query('query'),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([], 500);
            }
        }

    /* ============================
       BARCODE SEARCH
    ============================ */
    public function productByBarcode($barcode)
        {
            try {
                $p = Product::with('inventory')
                    ->where('tenant_id', auth()->user()->tenant_id)
                    ->where('sku', $barcode)
                    ->first();

                if (!$p) return response()->json(null);

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price' => $p->price,
                    'sku' => $p->sku,
                    'stock' => $p->inventory->quantity ?? 0
                ];

            } catch (\Throwable $e) {
                \Log::error('Failed to fetch product by barcode', [
                    'user_id' => auth()->id(),
                    'barcode' => $barcode,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json(null, 500);
            }
        }

   /* ============================
       POS CHECKOUT
============================ */
public function posCheckout(Request $request)
{
    try {
        $request->validate([
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'customer_id' => 'nullable|exists:customers,id',
            'payment_method' => 'required|in:Cash,Mpesa,Credit',
            'mpesa_code' => 'nullable|string|max:10',
        ]);

        $receipt = null;
        $customer = null;

        DB::transaction(function () use ($request, &$receipt, &$customer) {
            $tenantId = auth()->user()->tenant_id;
            $session = auth()->user()->openRegister;

            if (!$session) {
                throw new \Exception("Register is not open. Please open register first.");
            }

            $total = 0;
            $customerData = null;

            // ----------------------------
            // Tenant-specific receipt number
            // ----------------------------
            $lastTransaction = Transaction::where('tenant_id', $tenantId)
                ->lockForUpdate() // prevents race conditions
                ->latest('receipt_number')
                ->first();

            $nextReceiptNumber = $lastTransaction ? $lastTransaction->receipt_number + 1 : 1;

            // ----------------------------
            // Credit requires customer
            // ----------------------------
            if ($request->payment_method === 'Credit' && !$request->customer_id) {
                throw new \Exception("Credit sale requires a customer");
            }

            // Fetch customer data if credit
            if ($request->payment_method === 'Credit' && $request->customer_id) {
                $customer = Customer::where('tenant_id', $tenantId)
                    ->findOrFail($request->customer_id);

                $customerData = [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'previous_credit' => $customer->credit ?? 0,
                ];
            }

            // ----------------------------
            // Create transaction
            // ----------------------------
            $transaction = Transaction::create([
                'customer_id' => $request->customer_id,
                'total_amount' => 0, // will update later
                'payment_method' => $request->payment_method,
                'status' => $request->payment_method === 'Credit' ? 'On Credit' : 'Paid',
                'register_session_id' => $session->id,
                'staff_id' => auth()->id(),
                'tenant_id' => $tenantId,
                'mpesa_code' => $request->payment_method === 'Mpesa' ? $request->mpesa_code : null,
                'receipt_number' => $nextReceiptNumber,
            ]);

            // ----------------------------
            // Process products
            // ----------------------------
            foreach ($request->products as $row) {
                $product = Product::where('tenant_id', $tenantId)->findOrFail($row['id']);
                $qty = (int)$row['quantity'];

                $inventory = Inventory::where('tenant_id', $tenantId)
                    ->where('product_id', $product->id)
                    ->lockForUpdate()
                    ->first();

                if (!$inventory) throw new \Exception("Inventory missing for " . $product->name);
                if ($inventory->quantity < $qty) throw new \Exception("Not enough stock for " . $product->name);

                $lineTotal = $qty * $product->price;

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'price' => $product->price,
                    'total' => $lineTotal,
                ]);

                $inventory->decrement('quantity', $qty);

                $total += $lineTotal;
            }

            // Update transaction total
            $transaction->update(['total_amount' => $total]);

            // Update customer credit if Credit sale
            if ($request->payment_method === 'Credit' && $customer) {
                $customer->increment('credit', $total);
                $customer->refresh();
                $customerData['credit_added'] = $total;
                $customerData['total_credit'] = $customer->credit;
            }

            // Save payment record
            TransactionPayment::create([
                'transaction_id' => $transaction->id,
                'amount' => $total,
                'method' => $request->payment_method,
                'reference' => $request->payment_method === 'Mpesa' ? $request->mpesa_code : null,
            ]);

            $transaction->load('items.product', 'customer');

            // Prepare receipt
            $receipt = [
                'id' => $transaction->id,
                'receipt_number' => $transaction->receipt_number,
                'total' => $total,
                'payment_method' => $transaction->payment_method,
                'mpesa_reference' => $transaction->mpesa_reference,
                'user' => auth()->user()->name,
                'customer' => $customerData,
                'items' => $transaction->items->map(fn ($i) => [
                    'name' => $i->product->name,
                    'qty' => $i->quantity,
                    'price' => (float)$i->price,
                    'total' => $i->quantity * $i->price,
                ]),
            ];
        });

        return response()->json([
            'success' => true,
            'receipt' => $receipt,
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => $e->errors(),
        ], 422);
    } catch (\Throwable $e) {
        \Log::error("POS CHECKOUT FAILED", [
            'user_id' => auth()->id(),
            'tenant_id' => auth()->user()->tenant_id,
            'request' => $request->all(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ]);
    }
}
    /* ============================
       DELETE TRANSACTION
    ============================ */
    public function destroy(Transaction $transaction)
        {
            try {
                if($transaction->tenant_id !== auth()->user()->tenant_id){
                    abort(403, 'Unauthorized');
                }

                DB::transaction(function() use ($transaction){
                    foreach($transaction->items as $item){
                        $inventory = Inventory::where('tenant_id', auth()->user()->tenant_id)
                            ->where('product_id', $item->product_id)
                            ->lockForUpdate()
                            ->first();

                        if($inventory){
                            $inventory->increment('quantity', $item->quantity);

                            \App\Models\StockMovement::create([
                                'product_id' => $item->product_id,
                                'user_id' => auth()->id(),
                                'change' => $item->quantity,
                                'type' => 'restock',
                                'reference' => 'Transaction #'.$transaction->id,
                            ]);
                        }
                    }

                    if($transaction->payment_method === 'Credit' && $transaction->customer_id){
                        Customer::where('tenant_id', auth()->user()->tenant_id)
                            ->find($transaction->customer_id)
                            ->decrement('credit', $transaction->total_amount);
                    }

                    $transaction->payments()->delete();
                    $transaction->delete();
                });

                return redirect()->route('transactions.index')
                    ->with('success','Transaction deleted successfully and stock restored!');

            } catch (\Throwable $e) {
                \Log::error('Failed to delete transaction', [
                    'user_id' => auth()->id(),
                    'tenant_id' => auth()->user()->tenant_id,
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return back()->withErrors('Unable to delete transaction. Please try again later.');
            }
        }
}