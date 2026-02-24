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
}