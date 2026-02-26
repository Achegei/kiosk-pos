<?php

namespace App\Http\Controllers\Admin\Tenants;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    // Show the form to edit default notes
    public function editDefaultNotes()
    {
        $tenant = Auth::user()->tenant; // current tenant
        return view('admin.tenants.settings.default_notes', compact('tenant'));
    }

    // Update default notes
    public function updateDefaultNotes(Request $request)
    {
        $request->validate([
            'default_notes' => 'nullable|string',
        ]);

        $tenant = Auth::user()->tenant;
        $tenant->default_notes = $request->input('default_notes');
        $tenant->save();

        return redirect()->back()->with('success', 'Default notes updated successfully!');
    }
}