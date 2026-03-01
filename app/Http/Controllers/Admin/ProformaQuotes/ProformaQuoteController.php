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
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

class ProformaQuoteController extends Controller
{
    // ================= LIST =================
    public function index()
    {
        try {
            $tenantId = auth()->user()->tenant_id;

            $quotes = ProformaQuote::with('customer', 'tenant')
                ->where('tenant_id', $tenantId)
                ->latest()
                ->paginate(20);

            return view('admin.quotes.index', compact('quotes'));
        } catch (\Throwable $e) {
            Log::error('Failed to load quotes list', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            abort(500, 'Unable to load quotes.');
        }
    }

// ================= CREATE PAGE =================
    public function create()
    {
        try {
            $user = auth()->user();
            $tenant = $user->tenant;

            $customers = Customer::where('tenant_id', $tenant->id)
                                ->orderBy('name')
                                ->get();

            $defaultNotes = $tenant->default_notes ?? '';

            return view('admin.quotes.create', compact('tenant', 'customers', 'defaultNotes'));
        } catch (\Throwable $e) {
            Log::error('Failed to load quote creation page', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            abort(500, 'Unable to load quote creation page.');
        }
    }

    // ================= SHOW =================
    public function show(ProformaQuote $quote)
        {
            try {
                abort_if($quote->tenant_id !== auth()->user()->tenant_id, 403);

                $quote->load(['items.product', 'customer', 'staff']);

                return view('admin.quotes.show', compact('quote'));
            } catch (\Throwable $e) {
                Log::error('Failed to show quote', [
                    'user_id' => auth()->id(),
                    'quote_id' => $quote->id,
                    'error' => $e->getMessage()
                ]);

                abort(500, 'Unable to show quote.');
            }
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

            // Handle customer
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

            // Generate tenant-specific quote number safely
            $lastQuote = ProformaQuote::where('tenant_id', $tenantId)
                ->whereNotNull('quote_number')
                ->orderByDesc('quote_number')
                ->lockForUpdate()
                ->first();

            $nextNumber = $lastQuote ? (int) str_replace('PFQ-', '', $lastQuote->quote_number) + 1 : 1;
            $quoteNumber = 'PFQ-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

            // Company address snapshot
            $companyAddress = trim(($tenant->street_address ?? '') . ' ' .
                                   ($tenant->building_name ?? '') . ' ' .
                                   ($tenant->office_number ?? ''));

            // Create quote
            $quote = ProformaQuote::create([
                'quote_number' => $quoteNumber,
                'tenant_id' => $tenantId,
                'staff_id' => $staffId,
                'customer_id' => $customerId,
                'company_name' => $tenant->name,
                'company_email' => $tenant->email,
                'company_phone' => $tenant->phone,
                'company_address' => $companyAddress,
                'company_logo' => $tenant->logo,
                'client_name' => $request->client_name,
                'client_email' => $request->client_email,
                'client_phone' => $request->client_phone,
                'client_address' => $request->client_address,
                'tax_percent' => $request->tax_percent ?? 0,
                'discount' => $request->discount ?? 0,
                'notes' => $request->notes,
                'expiry_date' => $request->expiry_date,
                'status' => 'Draft',
                'total_amount' => 0
            ]);

            // Add items
            $total = 0;
            foreach ($request->items as $item) {
                $product = Product::where('tenant_id', $tenantId)->findOrFail($item['product_id']);
                $lineTotal = $product->price * (int)$item['qty'];

                ProformaQuoteItem::create([
                    'proforma_quote_id' => $quote->id,
                    'product_id' => $product->id,
                    'quantity' => (int)$item['qty'],
                    'price' => $product->price,
                    'total' => $lineTotal
                ]);

                $total += $lineTotal;
            }

            // Apply tax & discount
            $total = max($total + ($total * ($request->tax_percent ?? 0)/100) - ($request->discount ?? 0), 0);
            $quote->update(['total_amount' => $total]);

            DB::commit();

            return redirect()->route('quotes.show', $quote->id)
                             ->with('success', 'Quote created successfully!');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to store quote', [
                'user_id' => auth()->id(),
                'request' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return back()->withInput()->with('error', 'Failed to create quote.');
        }
    }

    // ================= EDIT =================
    public function edit(ProformaQuote $quote)
    {
        try {
            abort_if($quote->tenant_id !== auth()->user()->tenant_id, 403);

            $quote->load('items.product');
            $tenant = auth()->user()->tenant;

            $customers = Customer::where('tenant_id', $tenant->id)
                                 ->orderBy('name')
                                 ->get();

            return view('admin.quotes.edit', compact('quote','tenant','customers'));
        } catch (\Throwable $e) {
            Log::error('Failed to load edit quote', [
                'user_id' => auth()->id(),
                'quote_id' => $quote->id,
                'error' => $e->getMessage()
            ]);

            abort(500, 'Unable to load quote edit page.');
        }
    }

     // ================= UPDATE =================
    public function update(Request $request, ProformaQuote $quote)
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
            abort_if($quote->tenant_id !== auth()->user()->tenant_id, 403);

            $tenantId = $quote->tenant_id;

            // Handle customer
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

            // Update quote
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

            // Update items
            $quote->items()->delete();
            $total = 0;

            foreach ($request->items as $item) {
                $product = Product::where('tenant_id', $tenantId)->findOrFail($item['product_id']);
                $lineTotal = $product->price * (int)$item['qty'];

                ProformaQuoteItem::create([
                    'proforma_quote_id' => $quote->id,
                    'product_id' => $product->id,
                    'quantity' => (int)$item['qty'],
                    'price' => $product->price,
                    'total' => $lineTotal
                ]);

                $total += $lineTotal;
            }

            $total = max($total + ($total * $taxPercent / 100) - $discount, 0);
            $quote->update(['total_amount' => $total]);

            DB::commit();

            return redirect()->route('quotes.show', $quote->id)
                             ->with('success', 'Quote updated successfully!');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to update quote', [
                'user_id' => auth()->id(),
                'quote_id' => $quote->id,
                'request' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return back()->withInput()->with('error', 'Failed to update quote.');
        }
    }

  // ================= DESTROY =================
    public function destroy(ProformaQuote $quote)
    {
        DB::beginTransaction();

        try {
            abort_if($quote->tenant_id !== auth()->user()->tenant_id, 403);

            $quote->items()->delete();
            $quote->delete();

            DB::commit();

            return redirect()->route('quotes.index')
                             ->with('success', 'Quote deleted successfully!');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to delete quote', [
                'user_id' => auth()->id(),
                'quote_id' => $quote->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to delete quote.');
        }
    }

   
    // ================= SEARCH PRODUCTS =================
    public function searchProducts(Request $request)
    {
        try {
            $q = trim($request->query('q',''));
            if (!$q) return response()->json([]);

            $tenantId = auth()->user()->tenant_id;

            $products = Product::where('tenant_id', $tenantId)
                ->where(function($query) use ($q) {
                    $query->where('name', 'LIKE', "%{$q}%")
                          ->orWhere('sku', 'LIKE', "%{$q}%");
                })
                ->select('id','name','price','sku')
                ->limit(20)
                ->get();

            return response()->json($products);
        } catch (\Throwable $e) {
            Log::error('Product search failed', [
                'user_id' => auth()->id(),
                'query' => $request->query('q'),
                'error' => $e->getMessage()
            ]);
            return response()->json([], 500);
        }
    }

// ================= SEARCH CUSTOMERS =================
    public function searchCustomers(Request $request)
    {
        try {
            $q = trim($request->query('q',''));
            if (!$q) return response()->json([]);

            $tenantId = auth()->user()->tenant_id;

            $customers = Customer::where('tenant_id', $tenantId)
                ->where('name', 'LIKE', "%{$q}%")
                ->select('id','name','email','phone')
                ->limit(20)
                ->get();

            return response()->json($customers->map(fn($c)=>['id'=>$c->id,'text'=>$c->name]));
        } catch (\Throwable $e) {
            Log::error('Customer search failed', [
                'user_id' => auth()->id(),
                'query' => $request->query('q'),
                'error' => $e->getMessage()
            ]);
            return response()->json([], 500);
        }
    }
   // ================= CONVERT TO INVOICE =================
    public function convert(ProformaQuote $quote)
    {
        DB::beginTransaction();

        try {
            abort_if($quote->tenant_id !== auth()->user()->tenant_id, 403);

            if (Invoice::where('proforma_quote_id', $quote->id)->exists()) {
                return back()->with('info', 'This quote has already been converted to an invoice.');
            }

            $user = auth()->user();
            $tenant = $user->tenant;

            // Generate invoice number safely
            $lastInvoice = Invoice::where('tenant_id', $tenant->id)
                ->whereNotNull('invoice_number')
                ->orderByDesc('invoice_number')
                ->lockForUpdate()
                ->first();

            $nextNumber = $lastInvoice ? (int) str_replace('INV-', '', $lastInvoice->invoice_number) + 1 : 1;
            $invoiceNumber = 'INV-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

            // Create invoice
            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'tenant_id' => $tenant->id,
                'staff_id' => $user->id,
                'customer_id' => $quote->customer_id,
                'proforma_quote_id' => $quote->id,
                'company_name' => $tenant->name,
                'company_email' => $tenant->email,
                'company_phone' => $tenant->phone,
                'company_address' => trim(($tenant->street_address ?? '').' '.($tenant->building_name ?? '').' '.($tenant->office_number ?? '')),
                'company_logo' => $tenant->logo,
                'client_name' => $quote->client_name,
                'client_email' => $quote->client_email,
                'client_phone' => $quote->client_phone,
                'client_address' => $quote->client_address,
                'tax_percent' => $quote->tax_percent,
                'discount' => $quote->discount,
                'notes' => $quote->notes,
                'expiry_date' => $quote->expiry_date,
                'status' => 'Pending',
                'total_amount' => $quote->total_amount,
            ]);

            foreach ($quote->items as $item) {
                $invoice->items()->create([
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->total
                ]);
            }

            $quote->update(['status' => 'converted']);

            DB::commit();

            return redirect()->route('invoices.show', $invoice->id)
                             ->with('success', 'Quote converted to Invoice successfully!');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to convert quote to invoice', [
                'user_id' => auth()->id(),
                'quote_id' => $quote->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to convert quote to invoice.');
        }
    }

    // ================= DOWNLOAD PDF =================
    public function downloadPdf(ProformaQuote $quote)
    {
        try {
            abort_if($quote->tenant_id !== auth()->user()->tenant_id, 403);

            $quote->load('items.product', 'tenant');

            $pdf = Pdf::loadView('admin.quotes.pdf', compact('quote'))
                      ->setPaper('a4', 'portrait');

            return $pdf->stream('Quote-'.$quote->quote_number.'.pdf');

        } catch (\Throwable $e) {
            Log::error('Failed to generate quote PDF', [
                'user_id' => auth()->id(),
                'quote_id' => $quote->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to generate quote PDF.');
        }
    }
}