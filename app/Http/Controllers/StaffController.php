<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Roles this user can manage
        $manageableRoles = User::creatableRoles($user);

        // Only show manageable users
        $users = User::whereIn('role', $manageableRoles)
                    ->orderBy('id','desc')
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
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|confirmed|min:6',
        'role' => 'required|in:super_admin,admin,supervisor,staff'
    ]);

    // SECURITY: restrict role creation
    if(auth()->user()->isAdmin() && $request->role === 'super_admin'){
        abort(403);
    }

    User::create([
        'name'=>$request->name,
        'email'=>$request->email,
        'password'=>bcrypt($request->password),
        'role'=>$request->role,
        'can_pos'=>$request->has('can_pos')
    ]);


    return redirect()->route('staff.create')->with('success','Staff created');
}

    public function edit(User $staff)
        {
            $roles = User::creatableRoles(auth()->user());

            return view('staff.edit', [
                'user' => $staff,
                'roles' => $roles
            ]);
        }


public function update(Request $request, User $staff)
{
    $data = $request->validate([
        'name'=>'required|string|max:255',
        'email'=>'required|email',
        'role'=>'required|string',
        'password'=>'nullable|min:6|confirmed',
        'can_pos'=>'nullable|boolean'
    ]);

    if(!empty($data['password'])){
        $staff->password = bcrypt($data['password']);
    }

    $staff->name = $data['name'];
    $staff->email = $data['email'];
    $staff->role = $data['role'];
    $staff->can_pos = $request->has('can_pos');

    $staff->save();

    return redirect()->route('staff.index')
        ->with('success','Staff updated');
}


}