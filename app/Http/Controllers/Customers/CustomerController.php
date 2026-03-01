<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index()
    {
        try {
            $customers = Customer::where('tenant_id', auth()->user()->tenant_id)
                ->latest()
                ->paginate(10);

            return view('customers.index', compact('customers'));
        } catch (\Throwable $e) {
            \Log::channel('pos')->error('Failed to load customers', [
                'tenant_id' => auth()->user()->tenant_id ?? null,
                'user_id' => auth()->id() ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors('Unable to load customers. Please try again later.');
        }
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create()
    {
        try {
            return view('customers.create');
        } catch (\Throwable $e) {
            \Log::channel('pos')->error('Failed to show customer creation form', [
                'tenant_id' => auth()->user()->tenant_id ?? null,
                'user_id' => auth()->id() ?? null,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors('Unable to show form. Please try again.');
        }
    }

    /**
     * Store a newly created customer in storage.
     */
   public function store(Request $request)
        {
            $request->validate([
                'name' => 'required|string|max:255',
                'phone' => [
                    'required',
                    'string',
                    Rule::unique('customers')->where(fn($q) => $q->where('tenant_id', auth()->user()->tenant_id)),
                ],
                'email' => [
                    'nullable',
                    'email',
                    Rule::unique('customers')->where(fn($q) => $q->where('tenant_id', auth()->user()->tenant_id)),
                ],
                'credit' => 'nullable|numeric|min:0',
            ]);

            try {
                $customer = Customer::create([
                    'name' => $request->name,
                    'phone' => $request->phone,
                    'email' => $request->email ?? null,
                    'credit' => $request->credit ?? 0,
                    'tenant_id' => auth()->user()->tenant_id,
                ]);

                return redirect()->route('customers.index')
                    ->with('success', 'Customer created successfully!');
            } catch (\Throwable $e) {
                \Log::channel('pos')->error('Failed to store customer', [
                    'tenant_id' => auth()->user()->tenant_id ?? null,
                    'user_id' => auth()->id() ?? null,
                    'data' => $request->all(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return back()->withErrors('Unable to create customer. Please try again.');
            }
        }

    /**
     * QUICK CREATE CUSTOMER FROM POS (POS popup)
     */
    public function quickStore(Request $request)
        {
            $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'nullable|string',
                'email' => 'nullable|email',
            ]);

            try {
                $customer = Customer::create([
                    'name' => $request->name,
                    'phone' => $request->phone ?? null,
                    'email' => $request->email ?? null,
                    'credit' => 0,
                    'tenant_id' => auth()->user()->tenant_id,
                ]);

                return response()->json($customer);
            } catch (\Throwable $e) {
                \Log::channel('pos')->error('Failed to quick store customer', [
                    'tenant_id' => auth()->user()->tenant_id ?? null,
                    'user_id' => auth()->id() ?? null,
                    'data' => $request->all(),
                    'error' => $e->getMessage(),
                ]);

                return response()->json(['error' => 'Unable to add customer.'], 500);
            }
        }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit(Customer $customer)
        {
            try {
                if ($customer->tenant_id !== auth()->user()->tenant_id) {
                    abort(403, 'Unauthorized for this tenant.');
                }

                return view('customers.edit', compact('customer'));
            } catch (\Throwable $e) {
                \Log::channel('pos')->error('Failed to load customer for edit', [
                    'tenant_id' => auth()->user()->tenant_id ?? null,
                    'user_id' => auth()->id() ?? null,
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                ]);

                return back()->withErrors('Unable to edit customer. Please try again.');
            }
        }

    /**
     * Update the specified customer in storage.
     */
    public function update(Request $request, Customer $customer)
        {
            $request->validate([
                'name' => 'required|string|max:255',
                'phone' => [
                    'required',
                    'string',
                    Rule::unique('customers')->ignore($customer->id)
                        ->where(fn($q) => $q->where('tenant_id', auth()->user()->tenant_id)),
                ],
                'email' => [
                    'nullable',
                    'email',
                    Rule::unique('customers')->ignore($customer->id)
                        ->where(fn($q) => $q->where('tenant_id', auth()->user()->tenant_id)),
                ],
                'credit' => 'nullable|numeric|min:0',
            ]);

            try {
                if ($customer->tenant_id !== auth()->user()->tenant_id) {
                    abort(403, 'Unauthorized for this tenant.');
                }

                $customer->update($request->only(['name','phone','email','credit']));

                return redirect()->route('customers.index')
                    ->with('success', 'Customer updated successfully!');
            } catch (\Throwable $e) {
                \Log::channel('pos')->error('Failed to update customer', [
                    'tenant_id' => auth()->user()->tenant_id ?? null,
                    'user_id' => auth()->id() ?? null,
                    'customer_id' => $customer->id,
                    'data' => $request->all(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return back()->withErrors('Unable to update customer. Please try again.');
            }
        }

    /**
     * Remove the specified customer from storage.
     */
    public function destroy(Customer $customer)
        {
            try {
                if ($customer->tenant_id !== auth()->user()->tenant_id) {
                    abort(403, 'Unauthorized for this tenant.');
                }

                $customer->delete();

                return redirect()->route('customers.index')
                    ->with('success', 'Customer deleted successfully!');
            } catch (\Throwable $e) {
                \Log::channel('pos')->error('Failed to delete customer', [
                    'tenant_id' => auth()->user()->tenant_id ?? null,
                    'user_id' => auth()->id() ?? null,
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                ]);

                return back()->withErrors('Unable to delete customer. Please try again.');
            }
        }
}