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

    // ------------------- INDEX -------------------
    public function index()
    {
        try {
            $user = auth()->user();
            $manageableRoles = User::creatableRoles($user);

            // ✅ TENANT SAFE
            $users = User::where('tenant_id', $this->tenantId())
                ->whereIn('role', $manageableRoles)
                ->orderByDesc('id')
                ->get();

            return view('staff.index', compact('users','manageableRoles'));

        } catch (\Throwable $e) {
            Log::error('Failed to load staff list', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            abort(500, 'Unable to load staff list.');
        }
    }

    // ------------------- CREATE -------------------
    public function create()
    {
        try {
            $roles = User::creatableRoles(auth()->user());
            return view('staff.create', compact('roles'));
        } catch (\Throwable $e) {
            Log::error('Failed to load staff create form', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            abort(500, 'Unable to load staff creation form.');
        }
    }

    // ------------------- STORE -------------------
    public function store(Request $request)
    {
        $tenantId = $this->tenantId();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->where(fn($q) => $q->where('tenant_id', $tenantId))
            ],
            'password' => 'required|confirmed|min:6',
            'role' => 'required|in:super_admin,admin,supervisor,staff'
        ]);

        try {
            // ✅ SECURITY HARD STOP
            if(auth()->user()->isAdmin() && $data['role']==='super_admin'){
                abort(403);
            }

            User::create([
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
                'can_pos' => $request->boolean('can_pos')
            ]);

            return redirect()->route('staff.create')
                ->with('success','Staff created');

        } catch (\Throwable $e) {
            Log::error('Failed to create staff', [
                'user_id' => auth()->id(),
                'input' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create staff. Please try again.');
        }
    }

    // ------------------- EDIT -------------------
    public function edit($id)
    {
        try {
            $roles = User::creatableRoles(auth()->user());

            // ✅ TENANT SAFE FIND
            $staff = User::where('tenant_id', $this->tenantId())
                ->findOrFail($id);

            return view('staff.edit', [
                'user' => $staff,
                'roles' => $roles
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to load staff edit form', [
                'user_id' => auth()->id(),
                'staff_id' => $id,
                'error' => $e->getMessage()
            ]);

            abort(500, 'Unable to load staff edit form.');
        }
    }

    // ------------------- UPDATE -------------------
    public function update(Request $request, $id)
    {
        try {
            $staff = User::where('tenant_id', $this->tenantId())
                ->findOrFail($id);

            $tenantId = $this->tenantId();

            $data = $request->validate([
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'email',
                    Rule::unique('users')->ignore($staff->id)->where(fn($q) => $q->where('tenant_id', $tenantId))
                ],
                'role' => 'required|string',
                'password' => 'nullable|min:6|confirmed',
                'can_pos' => 'nullable|boolean'
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

        } catch (\Throwable $e) {
            Log::error('Failed to update staff', [
                'user_id' => auth()->id(),
                'staff_id' => $id,
                'input' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update staff. Please try again.');
        }
    }
}