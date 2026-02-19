<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{

    /**
     * Display a listing of customers.
     */
    public function index()
    {
        $customers = Customer::latest()->paginate(10);
        return view('customers.index', compact('customers'));
    }


    /**
     * Show the form for creating a new customer.
     */
    public function create()
    {
        return view('customers.create');
    }


    /**
     * Store a newly created customer in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'   => 'required|string|max:255',
            'phone'  => 'required|string|unique:customers,phone',
            'email'  => 'nullable|email|unique:customers,email',
            'credit' => 'nullable|numeric|min:0',
        ]);

        // Ensure credit defaults to 0 if empty
        if(!isset($validated['credit'])){
            $validated['credit'] = 0;
        }

        Customer::create($validated);

        return redirect()->route('customers.index')
            ->with('success', 'Customer created successfully!');
    }


    /**
     * 🔥 QUICK CREATE CUSTOMER FROM POS (VERY IMPORTANT)
     * Used by the POS "New Customer" popup button
     */
    public function quickStore(Request $request)
    {

        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string',
            'email' => 'nullable|email'
        ]);

        $customer = Customer::create([
            'name'   => $validated['name'],
            'phone'  => $validated['phone'] ?? null,
            'email'  => $validated['email'] ?? null,
            'credit' => 0
        ]);

        return response()->json($customer);
    }


    /**
     * Show the form for editing the specified customer.
     */
    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }


    /**
     * Update the specified customer in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name'   => 'required|string|max:255',
            'phone'  => 'required|string|unique:customers,phone,' . $customer->id,
            'email'  => 'nullable|email|unique:customers,email,' . $customer->id,
            'credit' => 'nullable|numeric|min:0',
        ]);

        $customer->update($validated);

        return redirect()->route('customers.index')
            ->with('success', 'Customer updated successfully!');
    }


    /**
     * Remove the specified customer from storage.
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Customer deleted successfully!');
    }

}
