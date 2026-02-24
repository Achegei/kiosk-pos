<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Services\TenantInitializer;

class TenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::latest()->get();
        return view('admin.tenants.index', compact('tenants'));
    }

    public function create()
    {
        return view('admin.tenants.create');
    }

            public function edit(Tenant $tenant)
        {
            return view('admin.tenants.edit', compact('tenant'));
        }

        public function update(Request $request, Tenant $tenant)
        {
            $request->validate([
                'business_name' => 'required|string|max:255',
                'phone' => 'nullable|string|max:20',
                'status' => 'required|string|in:trial,active,inactive',
            ]);

            // Map form fields to DB columns
            $tenant->update([
                'name' => $request->business_name,
                'phone' => $request->phone,
                'subscription_status' => $request->status,
            ]);

            return redirect()->route('admin.tenants.index')
                            ->with('success','Tenant updated successfully.');
        }


    public function store(Request $request)
    {
        $data = $request->validate([
            'business_name'=>'required',
            'phone'=>'nullable',
            'admin_name'=>'required',
            'admin_email'=>'required|email|unique:users,email',
            'admin_password'=>'required|min:6'
        ]);

        $tenant = Tenant::create([
            'name'=>$data['business_name'],
            'phone'=>$data['phone'],
            'subscription_status'=>'trial'
        ]);

        TenantInitializer::setup($tenant,[
            'name'=>$data['admin_name'],
            'email'=>$data['admin_email'],
            'password'=>$data['admin_password']
        ]);

        return redirect()->route('admin.tenants.index')
               ->with('success','Tenant created!');
    }

    public function show($tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        return view('admin.tenants.show', compact('tenant'));
    }

    public function destroy(Tenant $tenant)
    {
        $tenant->delete();
        return redirect()->route('admin.tenants.index')->with('success','Tenant deleted.');
    }
}