<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use Illuminate\Http\Request;

class CashMovementController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'register_session_id' => 'nullable|exists:register_sessions,id',
            'type' => 'required|in:drop,expense,payout,deposit,adjustment',
            'amount' => 'required|numeric|min:0.01',
            'note' => 'nullable|string|max:255'
        ]);

        CashMovement::create([
            'tenant_id' => auth()->user()->tenant_id,
            'register_session_id' => $request->register_session_id,
            'user_id' => auth()->id(),
            'type' => $request->type,
            'amount' => $request->amount,
            'note' => $request->note
        ]);

        return response()->json(['success'=>true]);
    }

    
}
