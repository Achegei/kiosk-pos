<?php

namespace App\Http\Controllers\Admin\Invoices;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice; 
use App\Models\InvoiceItem;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Customer; 
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // ------------------- INDEX -------------------
    public function index()
    {
        try {
            $tenantId = auth()->user()->tenant_id;

            $invoices = Invoice::with('customer', 'staff')
                ->where('tenant_id', $tenantId)
                ->latest()
                ->paginate(20);

            return view('admin.invoices.index', compact('invoices'));
        } catch (\Throwable $e) {
            Log::error('Failed to load invoices', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            abort(500, 'Unable to load invoices.');
        }
    }


    /**
     * Show the form for creating a new resource.
     */
    // ------------------- CREATE -------------------
    public function create()
    {
        try {
            $tenant = auth()->user()->tenant;

            $customers = Customer::where('tenant_id', $tenant->id)->orderBy('name')->get();
            $products = Product::where('tenant_id', $tenant->id)->orderBy('name')->get();

            return view('admin.invoices.create', compact('tenant','customers','products'));
        } catch (\Throwable $e) {
            Log::error('Failed to load invoice creation form', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            abort(500, 'Unable to load invoice form.');
        }
    }

    /**
     * Store a newly created resource in storage.
     */
     // ------------------- STORE -------------------
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'customer_id' => 'nullable|exists:customers,id',
            'client_name' => 'nullable|string|max:255',
            'client_email' => 'nullable|email|max:255',
            'client_phone' => 'nullable|string|max:255',
            'client_address' => 'nullable|string|max:255',
            'tax_percent' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'status' => 'required|in:Paid,Pending,On Credit',
        ]);

        DB::beginTransaction();
        try {
            $user = auth()->user();
            $tenant = $user->tenant;
            $customerId = $request->customer_id;

            // Auto-create customer if provided details exist
            if (!$customerId && $request->client_name) {
                $customer = Customer::create([
                    'tenant_id' => $tenant->id,
                    'name' => $request->client_name,
                    'email' => $request->client_email,
                    'phone' => $request->client_phone,
                    'address' => $request->client_address,
                ]);
                $customerId = $customer->id;
            }

            // Create invoice
            $invoice = Invoice::create([
                'tenant_id' => $tenant->id,
                'staff_id' => $user->id,
                'customer_id' => $customerId,
                'client_name' => $request->client_name,
                'client_email' => $request->client_email,
                'client_phone' => $request->client_phone,
                'client_address' => $request->client_address,
                'notes' => $request->notes,
                'status' => $request->status,
                'tax_percent' => 0,
                'discount' => 0,
                'total_amount' => 0,
            ]);

            $subtotal = 0;
            foreach ($request->items as $item) {

                $product = Product::where('tenant_id', $tenant->id)
                                ->findOrFail($item['product_id']);

                $qty = (int) $item['qty'];
                $lineTotal = $product->price * $qty;

                // ✅ CHECK & LOCK INVENTORY
                $inventory = Inventory::where('product_id', $product->id)
                    ->where('tenant_id', $tenant->id)
                    ->lockForUpdate()
                    ->first();

                if (!$inventory) {
                    throw new \Exception("No inventory record for {$product->name}");
                }

                if ($inventory->quantity < $qty) {
                    throw new \Exception("Insufficient stock for {$product->name}");
                }

                // ✅ REDUCE STOCK
                $inventory->quantity -= $qty;
                $inventory->save();

                // ✅ CREATE INVOICE ITEM
                $invoice->items()->create([
                    'product_id' => $product->id,
                    'quantity'   => $qty,
                    'price'      => $product->price,
                    'total'      => $lineTotal,
                ]);

                $subtotal += $lineTotal;
            }

            $taxPercent = (float) $request->tax_percent;
            $discount = (float) $request->discount;
            $taxAmount = ($subtotal * $taxPercent) / 100;
            $total = max($subtotal + $taxAmount - $discount, 0);

            $invoice->update([
                'tax_percent' => $taxPercent,
                'discount' => $discount,
                'total_amount' => $total,
            ]);

            DB::commit();

            return redirect()->route('invoices.show', $invoice->id)
                             ->with('success','Invoice created successfully!');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to create invoice', [
                'user_id' => auth()->id(),
                'request' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return back()->withInput()->with('error','Failed to create invoice.');
        }
    }
    /**
     * Display the specified resource.
     */
   
    // ------------------- SHOW -------------------
    public function show(Invoice $invoice)
    {
        try {
            abort_if($invoice->tenant_id !== auth()->user()->tenant_id, 403);

            $invoice->load([
                'items.product',
                'customer',
                'staff',
                'quote',
                'tenant',
            ]);

            return view('admin.invoices.show', compact('invoice'));
        } catch (\Throwable $e) {
            Log::error('Failed to load invoice', [
                'user_id' => auth()->id(),
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
            abort(500, 'Unable to load invoice.');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    // Update Status
        // ------------------- UPDATE STATUS -------------------
    public function updateStatus(Request $request, Invoice $invoice)
    {
        try {
            abort_if($invoice->tenant_id !== auth()->user()->tenant_id, 403);

            $request->validate([
                'status' => 'required|in:Paid,Pending,On Credit',
            ]);

            $invoice->update([
                'status' => $request->status
            ]);

            return back()->with('success', "Invoice marked as {$request->status}");
        } catch (\Throwable $e) {
            Log::error('Failed to update invoice status', [
                'user_id' => auth()->id(),
                'invoice_id' => $invoice->id,
                'request' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return back()->with('error','Failed to update invoice status.');
        }
    }

    // Download saved invoice PDF
       // ------------------- DOWNLOAD PDF -------------------
    public function downloadPdf(Invoice $invoice)
        {
            try {
                abort_if($invoice->tenant_id !== auth()->user()->tenant_id, 403);

                $invoice->load(['items.product','customer','tenant']);

                $pdf = Pdf::loadView('admin.invoices.pdf', compact('invoice'))
                        ->setPaper('a4', 'portrait');

                return $pdf->download("Invoice-{$invoice->invoice_number}.pdf");
            } catch (\Throwable $e) {
                Log::error('Failed to download invoice PDF', [
                    'user_id' => auth()->id(),
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage()
                ]);

                return back()->with('error','Failed to generate invoice PDF.');
            }
        }

    // Generate temporary PDF from live cart
    // ------------------- GENERATE TEMP PDF -------------------
    public function generatePdf(Request $request)
    {
        try {
            $data = $request->all();
            $items = json_decode($data['items_json'] ?? '[]', true);

            $invoice = (object)[
                'invoice_number' => 'TEMP-' . rand(1000,9999),
                'client_name' => $data['client_name'] ?? '',
                'client_email' => $data['client_email'] ?? '',
                'client_phone' => $data['client_phone'] ?? '',
                'client_address' => $data['client_address'] ?? '',
                'tax_percent' => $data['tax_percent'] ?? 0,
                'discount' => $data['discount'] ?? 0,
                'total_amount' => $data['total_amount'] ?? 0,
                'items' => collect($items)->map(function($i){
                    return (object)[
                        'product' => (object)['name' => $i['name'] ?? ''],
                        'quantity' => $i['qty'] ?? 1,
                        'price' => $i['price'] ?? 0
                    ];
                })
            ];

            return Pdf::loadView('admin.invoices.pdf', compact('invoice'))
                      ->setPaper('a4', 'portrait')
                      ->stream('Invoice_'.$invoice->invoice_number.'.pdf');

        } catch (\Throwable $e) {
            Log::error('Failed to generate temporary invoice PDF', [
                'user_id' => auth()->id(),
                'request' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return back()->with('error','Failed to generate temporary invoice PDF.');
        }
    }

    public function returnItem(Request $request, Invoice $invoice)
{
    // Only allow same tenant
    abort_if($invoice->tenant_id !== auth()->user()->tenant_id, 403);

    $request->validate([
        'invoice_item_id' => 'required|exists:invoice_items,id',
        'quantity' => 'required|integer|min:1',
    ]);

    DB::beginTransaction();

    try {
        // Lock the invoice item row
        $invoiceItem = InvoiceItem::where('id', $request->invoice_item_id)
            ->where('invoice_id', $invoice->id)
            ->lockForUpdate()
            ->firstOrFail();

        $qtyToReturn = (int) $request->quantity;

        $remainingReturnable = $invoiceItem->quantity - $invoiceItem->returned_quantity;

        if ($qtyToReturn > $remainingReturnable) {
            throw new \Exception("Return quantity cannot exceed remaining sold quantity ($remainingReturnable).");
        }

        // Lock inventory row
        $inventory = Inventory::where('product_id', $invoiceItem->product_id)
            ->where('tenant_id', $invoice->tenant_id)
            ->lockForUpdate()
            ->firstOrFail();

        // Increase inventory stock
        $inventory->quantity += $qtyToReturn;
        $inventory->save();

        // Update returned quantity on invoice item
        $invoiceItem->returned_quantity += $qtyToReturn;
        $invoiceItem->save();

        // Recalculate invoice totals
        $invoice->load('items');

        $subtotal = 0;
        foreach ($invoice->items as $item) {
            $effectiveQty = max(0, $item->quantity - $item->returned_quantity);
            $subtotal += $effectiveQty * $item->price;
        }

        $taxAmount = ($subtotal * ($invoice->tax_percent ?? 0)) / 100;
        $newTotal = max($subtotal + $taxAmount - ($invoice->discount ?? 0), 0);

        $invoice->total_amount = $newTotal;

        // Update invoice status
        if ($newTotal == 0) {
            $invoice->status = 'Returned';
        } elseif ($invoice->status === 'Paid') {
            $invoice->status = 'Adjusted';
        } elseif ($invoice->status === 'Pending' && $newTotal > 0) {
            $invoice->status = 'Pending';
        }

        $invoice->save();

        DB::commit();

        return back()->with('success', "Returned $qtyToReturn item(s) & invoice updated successfully.");

    } catch (\Throwable $e) {
        DB::rollBack();
        return back()->with('error', $e->getMessage());
    }
}
}
