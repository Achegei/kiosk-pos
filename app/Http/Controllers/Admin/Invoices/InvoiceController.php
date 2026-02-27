<?php

namespace App\Http\Controllers\Admin\Invoices;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice; 
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Customer; 
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;

        $invoices = Invoice::with('customer', 'staff')
            ->where('tenant_id', $tenantId)
            ->latest()
            ->paginate(20);

        return view('admin.invoices.index', compact('invoices'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $tenant = auth()->user()->tenant;

        $customers = Customer::where('tenant_id', $tenant->id)->orderBy('name')->get();
        $products = Product::where('tenant_id', $tenant->id)->orderBy('name')->get();

        return view('admin.invoices.create', compact('tenant','customers','products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    $request->validate([
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.qty' => 'required|integer|min:1',

        'customer_id' => 'nullable|exists:customers,id',

        'client_name' => 'nullable|string|max:255',
        'client_email' => 'nullable|email|max:255',
        'client_name' => 'nullable|string|max:255',
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

        if(!$customerId && $request->client_name){
            $customer = Customer::create([
                'tenant_id' => $tenant->id,
                'name' => $request->client_name,
                'email' => $request->client_email,
                'phone' => $request->client_phone,
                'address' => $request->client_address,
            ]);
            $customerId = $customer->id;
        }

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'staff_id' => $user->id,
            'customer_id' => $customerId,

            'client_name' => $request->client_name,
            'client_email' => $request->client_email,
            'client_name' => $request->client_name,
            'client_phone' => $request->client_phone,
            'client_address' => $request->client_address,

            'notes' => $request->notes,
            'status' => $request->status,

            'tax_percent' => 0,
            'discount' => 0,
            'total_amount' => 0,
        ]);

        $subtotal = 0;

        foreach($request->items as $item){
            $product = Product::findOrFail($item['product_id']);
            $qty = (int) $item['qty'];
            $lineTotal = $product->price * $qty;

            $invoice->items()->create([
                'product_id' => $product->id,
                'quantity' => $qty,
                'price' => $product->price,
                'total' => $lineTotal,
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

    } catch(\Throwable $e){
        DB::rollBack();
        return back()->with('error',$e->getMessage());
    }
}

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice)
    {
        abort_if($invoice->tenant_id !== auth()->user()->tenant_id, 403);

        // Load all needed relations including tenant
        $invoice->load([
            'items.product',
            'customer',
            'staff',
            'quote',
            'tenant', // <-- Add this
        ]);

        return view('admin.invoices.show', compact('invoice'));
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
    public function updateStatus(Request $request, Invoice $invoice)
    {
        abort_if($invoice->tenant_id !== auth()->user()->tenant_id, 403);

        $request->validate([
            'status' => 'required|in:Paid,Pending,On Credit',
        ]);

        $invoice->update([
            'status' => $request->status
        ]);

        return back()->with('success', "Invoice marked as {$request->status}");
    }

    // Download saved invoice PDF
    public function downloadPdf(Invoice $invoice)
    {
        $invoice->load([
            'items.product',
            'customer',
            'tenant'
        ]);

        $pdf = Pdf::loadView('admin.invoices.pdf', compact('invoice'))
                  ->setPaper('a4', 'portrait');

        return $pdf->download("Invoice-{$invoice->invoice_number}.pdf");
    }

    // Generate temporary PDF from live cart
    public function generatePdf(Request $request)
    {
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
    }
}
