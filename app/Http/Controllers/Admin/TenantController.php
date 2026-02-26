<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Services\TenantInitializer;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\Transaction;

class TenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::latest()->get();
        return view('admin.tenants.index', compact('tenants'));
    }

    public function create()
    {
        $tenant = new Tenant();
        return view('admin.tenants.create', compact('tenant'));
    }

    public function edit(Tenant $tenant)
    {
        return view('admin.tenants.edit', compact('tenant'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_name'      => 'required|string|max:255',
            'phone'              => 'nullable|string|max:255',
            'street'             => 'nullable|string|max:255',
            'building'           => 'nullable|string|max:255',
            'shop_number'        => 'nullable|string|max:255',
            'logo'               => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'subscription_status'=> 'nullable|string|in:trial,active,expired',
            'expiry_date'        => 'nullable|date',
            'admin_name'         => 'required|string|max:255',
            'admin_email'        => 'required|email|unique:users,email',
            'admin_password'     => 'required|string|min:6',
        ]);

        $tenant = Tenant::create([
            'name'                => $data['business_name'],
            'phone'               => $data['phone'] ?? null,
            'street_address'      => $data['street'] ?? null,
            'building_name'       => $data['building'] ?? null,
            'office_number'       => $data['shop_number'] ?? null,
            'subscription_status' => $data['subscription_status'] ?? 'trial',
            'expiry_date'         => $data['expiry_date'] ?? null,
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $tenant->logo = $request->file('logo')->store('tenants/logos', 'public');
            $tenant->save();
        }

        // Initialize tenant admin
        TenantInitializer::setup($tenant, [
            'name'     => $data['admin_name'],
            'email'    => $data['admin_email'],
            'password' => $data['admin_password'],
        ]);

        return redirect()->route('admin.tenants.index')
               ->with('success','Tenant created!');
    }

    public function update(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'business_name'      => 'required|string|max:255',
            'phone'              => 'nullable|string|max:255',
            'street'             => 'nullable|string|max:255',
            'building'           => 'nullable|string|max:255',
            'shop_number'        => 'nullable|string|max:255',
            'logo'               => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'subscription_status'=> 'required|string|in:trial,active,expired',
            'expiry_date'        => 'nullable|date',
        ]);

        $tenant->update([
            'name'                => $data['business_name'],
            'phone'               => $data['phone'] ?? null,
            'street_address'      => $data['street'] ?? null,
            'building_name'       => $data['building'] ?? null,
            'office_number'       => $data['shop_number'] ?? null,
            'subscription_status' => $data['subscription_status'],
            'expiry_date'         => $data['expiry_date'] ?? null,
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $tenant->logo = $request->file('logo')->store('tenants/logos', 'public');
            $tenant->save();
        }

        return redirect()->route('admin.tenants.index')
                        ->with('success','Tenant updated successfully.');
    }

    public function show($tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        return view('admin.tenants.show', compact('tenant'));
    }

    public function destroy(Tenant $tenant)
    {
        $tenant->delete();
        return redirect()->route('admin.tenants.index')
                         ->with('success','Tenant deleted.');
    }

    public function export(Tenant $tenant): StreamedResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $filename = "transactions.csv";

        return response()->streamDownload(function () use ($tenantId, $tenant) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Transaction ID',
                'Customer',
                'Items',
                'Payment Method',
                'Status',
                'Total Amount',
                'Date'
            ]);

            Transaction::with(['customer', 'items.product'])
                ->where('tenant_id', $tenant->id)
                ->orderBy('id')
                ->chunk(500, function ($transactions) use ($handle) {
                    foreach ($transactions as $tx) {
                        $items = $tx->items
                            ->map(fn($i) => ($i->product->name ?? 'Unknown').' x'.$i->quantity)
                            ->join(' | ');

                        fputcsv($handle, [
                            $tx->id,
                            $tx->customer->name ?? 'Walk-in',
                            $items,
                            $tx->payment_method,
                            $tx->status,
                            $tx->total_amount,
                            $tx->created_at->format('Y-m-d H:i:s'),
                        ]);
                    }
                });

            fclose($handle);

        }, $filename);
    }
}