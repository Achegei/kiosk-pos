<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    private function tenantId()
    {
        return auth()->user()->tenant_id;
    }

    public function index()
    {
        $user = auth()->user();

        $manageableRoles = User::creatableRoles($user);

        // ✅ TENANT SAFE
        $users = User::where('tenant_id', $this->tenantId())
            ->whereIn('role', $manageableRoles)
            ->orderByDesc('id')
            ->get();

        return view('staff.index', compact('users','manageableRoles'));
    }

    public function create()
    {
        $roles = User::creatableRoles(auth()->user());
        return view('staff.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $tenantId = $this->tenantId();

        $data = $request->validate([
            'name' => 'required|string|max:255',

            // ✅ TENANT-SCOPED EMAIL UNIQUE
            'email' => [
                'required',
                'email',
                \Illuminate\Validation\Rule::unique('users')
                    ->where(fn($q)=>$q->where('tenant_id',$tenantId))
            ],

            'password' => 'required|confirmed|min:6',
            'role' => 'required|in:super_admin,admin,supervisor,staff'
        ]);

        // ✅ SECURITY HARD STOP
        if(auth()->user()->isAdmin() && $data['role']==='super_admin'){
            abort(403);
        }

        User::create([
            'tenant_id' => $tenantId, // ⭐ CRITICAL
            'name'=>$data['name'],
            'email'=>$data['email'],
            'password'=>Hash::make($data['password']),
            'role'=>$data['role'],
            'can_pos'=>$request->boolean('can_pos')
        ]);

        return redirect()->route('staff.create')
            ->with('success','Staff created');
    }

    public function edit($id)
    {
        $roles = User::creatableRoles(auth()->user());

        // ✅ TENANT SAFE FIND
        $staff = User::where('tenant_id',$this->tenantId())
            ->findOrFail($id);

        return view('staff.edit', [
            'user' => $staff,
            'roles' => $roles
        ]);
    }

    public function update(Request $request, $id)
    {
        $staff = User::where('tenant_id',$this->tenantId())
            ->findOrFail($id);

        $tenantId = $this->tenantId();

        $data = $request->validate([
            'name'=>'required|string|max:255',

            // ✅ tenant-safe unique except current
            'email'=>[
                'required','email',
                \Illuminate\Validation\Rule::unique('users')
                    ->ignore($staff->id)
                    ->where(fn($q)=>$q->where('tenant_id',$tenantId))
            ],

            'role'=>'required|string',
            'password'=>'nullable|min:6|confirmed',
            'can_pos'=>'nullable|boolean'
        ]);

        if(!empty($data['password'])){
            $staff->password = Hash::make($data['password']);
        }

        $staff->name = $data['name'];
        $staff->email = $data['email'];
        $staff->role = $data['role'];
        $staff->can_pos = $request->boolean('can_pos');

        $staff->save();

        return redirect()->route('staff.index')
            ->with('success','Staff updated');
    }
}