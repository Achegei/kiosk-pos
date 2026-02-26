<?php

namespace App\Http\Controllers\Admin\ProformaQuotes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProformaQuote;
use App\Models\ProformaQuoteItem;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Tenant;

class ProformaQuoteController extends Controller
{
    // ================= LIST =================
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;

        $quotes = ProformaQuote::with('customer', 'tenant')
            ->where('tenant_id', $tenantId)
            ->latest()
            ->paginate(20);

        return view('admin.quotes.index', compact('quotes'));
    }

        // ================= CREATE PAGE =================
    public function create()
    {
        $user = auth()->user();
        $tenant = $user->tenant;

        $customers = Customer::where('tenant_id', $tenant->id)
                            ->orderBy('name')
                            ->get();

        // Pass default notes to the view
        $defaultNotes = $tenant->default_notes;

        return view('admin.quotes.create', compact('tenant', 'customers', 'defaultNotes'));
    }

    // ================= SHOW PAGE =================
    public function show(ProformaQuote $quote)
    {
        abort_if($quote->tenant_id !== auth()->user()->tenant_id, 403);

        $quote->load(['items.product', 'customer', 'staff']);

        return view('admin.quotes.show', compact('quote'));
    }

    // ================= STORE QUOTE =================
    public function store(Request $request)
{
    $request->validate([
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.qty' => 'required|integer|min:1',

        'client_name' => 'required|string|max:255',
        'client_email' => 'nullable|email|max:255',
        'client_phone' => 'nullable|string|max:255',
        'client_address' => 'nullable|string|max:255',

        'tax_percent' => 'nullable|numeric|min:0',
        'discount' => 'nullable|numeric|min:0',
        'expiry_date' => 'nullable|date',
        'notes' => 'nullable|string',
    ]);

    DB::beginTransaction();

    try {
        $user = auth()->user();
        $tenant = $user->tenant;
        $tenantId = $tenant->id;
        $staffId = $user->id;

        // ---------------- HANDLE CUSTOMER ----------------
        $customerId = $request->customer_id;
        if (!$customerId) {
            $customer = Customer::create([
                'tenant_id' => $tenantId,
                'name' => $request->client_name,
                'email' => $request->client_email,
                'phone' => $request->client_phone,
                'address' => $request->client_address,
            ]);
            $customerId = $customer->id;
        }

        $taxPercent = (float) ($request->tax_percent ?? 0);
        $discount = (float) ($request->discount ?? 0);

        // ---------------- GENERATE TENANT-SPECIFIC QUOTE NUMBER ----------------
        $lastQuote = ProformaQuote::where('tenant_id', $tenantId)
                        ->whereNotNull('quote_number')
                        ->orderBy('id', 'desc')
                        ->first();

        $nextNumber = $lastQuote
            ? ((int) filter_var($lastQuote->quote_number, FILTER_SANITIZE_NUMBER_INT)) + 1
            : 1;

        $quoteNumber = 'PFQ-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        // ---------------- AUTO POPULATE COMPANY FROM TENANT ----------------
        $companyAddress = trim(
            ($tenant->street_address ?? '') . ' ' .
            ($tenant->building_name ?? '') . ' ' .
            ($tenant->office_number ?? '')
        );

        // ---------------- CREATE QUOTE ----------------
        $quote = ProformaQuote::create([
            'quote_number' => $quoteNumber,
            'tenant_id' => $tenantId,
            'staff_id' => $staffId,
            'customer_id' => $customerId,

            // AUTO FROM TENANT
            'company_name' => $tenant->name,
            'company_email' => $tenant->email,
            'company_phone' => $tenant->phone,
            'company_address' => $companyAddress,
            'company_logo' => $tenant->logo,

            // CLIENT INFO
            'client_name' => $request->client_name,
            'client_email' => $request->client_email,
            'client_phone' => $request->client_phone,
            'client_address' => $request->client_address,

            'tax_percent' => $taxPercent,
            'discount' => $discount,
            'notes' => $request->notes,
            'expiry_date' => $request->expiry_date,

            'status' => 'Draft',
            'total_amount' => 0
        ]);

        // ---------------- ADD ITEMS ----------------
        $total = 0;
        foreach ($request->items as $item) {
            $product = Product::where('tenant_id', $tenantId)
                              ->findOrFail($item['product_id']);

            $qty = (int) $item['qty'];
            $lineTotal = $product->price * $qty;

            ProformaQuoteItem::create([
                'proforma_quote_id' => $quote->id,
                'product_id' => $product->id,
                'quantity' => $qty,
                'price' => $product->price,
                'total' => $lineTotal
            ]);

            $total += $lineTotal;
        }

        // ---------------- APPLY TAX & DISCOUNT ----------------
        $total = max($total + ($total * $taxPercent / 100) - $discount, 0);

        $quote->update([
            'total_amount' => $total
        ]);

        DB::commit();

        return redirect()->route('quotes.show', $quote->id)
                         ->with('success', 'Quote created successfully!');

    } catch (\Throwable $e) {
        DB::rollBack();
        return back()->with('error', $e->getMessage());
    }
}

    // ================= EDIT =================
    public function edit(ProformaQuote $quote)
    {
        abort_if($quote->tenant_id !== auth()->user()->tenant_id, 403);

        $quote->load('items.product');

        $tenant = auth()->user()->tenant;

        $customers = Customer::where('tenant_id', $tenant->id)
                             ->orderBy('name')
                             ->get();

        return view('admin.quotes.edit', compact('quote','tenant','customers'));
    }

    // ================= UPDATE =================
    public function update(Request $request, ProformaQuote $quote)
    {
        abort_if($quote->tenant_id !== auth()->user()->tenant_id, 403);

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',

            'client_name' => 'required|string|max:255',
            'client_email' => 'nullable|email|max:255',
            'client_phone' => 'nullable|string|max:255',
            'client_address' => 'nullable|string|max:255',

            'tax_percent' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {

            $tenant = auth()->user()->tenant;

            $taxPercent = (float) ($request->tax_percent ?? 0);
            $discount = (float) ($request->discount ?? 0);

            // ---------------- HANDLE CUSTOMER ----------------
            $customerId = $request->customer_id;

            if (!$customerId) {
                $customer = Customer::create([
                    'tenant_id' => $quote->tenant_id,
                    'name' => $request->client_name,
                    'email' => $request->client_email,
                    'phone' => $request->client_phone,
                    'address' => $request->client_address,
                ]);
                $customerId = $customer->id;
            }

            // ---------------- UPDATE QUOTE ----------------
            $quote->update([
                'customer_id' => $customerId,

                'client_name' => $request->client_name,
                'client_email' => $request->client_email,
                'client_phone' => $request->client_phone,
                'client_address' => $request->client_address,

                'tax_percent' => $taxPercent,
                'discount' => $discount,
                'notes' => $request->notes,
                'expiry_date' => $request->expiry_date,
            ]);

            // ---------------- UPDATE ITEMS ----------------
            $quote->items()->delete();
            $total = 0;

            foreach ($request->items as $item) {

                $product = Product::where('tenant_id', $quote->tenant_id)
                                  ->findOrFail($item['product_id']);

                $qty = (int)$item['qty'];
                $lineTotal = $product->price * $qty;

                ProformaQuoteItem::create([
                    'proforma_quote_id' => $quote->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'price' => $product->price,
                    'total' => $lineTotal
                ]);

                $total += $lineTotal;
            }

            $total = max($total + ($total * $taxPercent / 100) - $discount, 0);

            $quote->update([
                'total_amount' => $total
            ]);

            DB::commit();

            return redirect()->route('quotes.show', $quote->id)
                             ->with('success', 'Quote updated successfully!');

        } catch (\Throwable $e) {

            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    // ================= DESTROY =================
    public function destroy(ProformaQuote $quote)
    {
        abort_if($quote->tenant_id !== auth()->user()->tenant_id, 403);

        DB::beginTransaction();

        try {

            $quote->items()->delete();
            $quote->delete();

            DB::commit();

            return redirect()->route('quotes.index')
                             ->with('success', 'Quote deleted successfully!');

        } catch (\Throwable $e) {

            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    // ================= API PRODUCT SEARCH =================
    public function searchProducts(Request $request)
    {
        $q = trim($request->query('q',''));
        if (!$q) return response()->json([]);

        $userTenantId = auth()->user()->tenant_id;

        $products = Product::where('tenant_id', $userTenantId)
            ->where(function($query) use ($q) {
                $query->where('name', 'LIKE', "%{$q}%")
                      ->orWhere('sku', 'LIKE', "%{$q}%");
            })
            ->select('id','name','price','sku')
            ->limit(20)
            ->get();

        return response()->json($products);
    }

    // ================= API CUSTOMER SEARCH =================
public function searchCustomers(Request $request)
{
    $q = trim($request->query('q',''));
    if (!$q) return response()->json([]);

    $userTenantId = auth()->user()->tenant_id;

    $customers = Customer::where('tenant_id', $userTenantId)
        ->where('name', 'LIKE', "%{$q}%")
        ->select('id','name','email','phone')
        ->limit(20)
        ->get();

    return response()->json($customers->map(fn($c) => [
        'id' => $c->id,
        'text' => $c->name
    ]));
}
// ================= CONVERT QUOTE TO INVOICE =================
public function convert(ProformaQuote $quote)
{
    // Ensure tenant ownership
    abort_if($quote->tenant_id !== auth()->user()->tenant_id, 403);

    DB::beginTransaction();
    try {
        // Create a new invoice from the quote
        $invoice = \App\Models\Invoice::create([
            'tenant_id' => $quote->tenant_id,
            'staff_id' => auth()->id(),
            'customer_id' => $quote->customer_id,
            'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . rand(1000,9999),
            'total_amount' => $quote->total_amount,
            'tax_percent' => $quote->tax_percent,
            'discount' => $quote->discount,
            'notes' => $quote->notes,
        ]);

        // Copy items
        foreach ($quote->items as $item) {
            $invoice->items()->create([
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'total' => $item->total,
            ]);
        }

        // Mark quote as converted
        $quote->update(['status' => 'converted']);

        DB::commit();

        return redirect()->route('invoices.show', $invoice->id)
                         ->with('success', 'Quote converted to Invoice successfully!');
    } catch (\Throwable $e) {
        DB::rollBack();
        return back()->with('error', $e->getMessage());
    }
}
}