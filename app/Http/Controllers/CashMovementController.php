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

        // ✅ SAFETY: must be logged in
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // ✅ SAFETY: auto attach open register if not provided
        $sessionId = $request->register_session_id 
            ?? optional(auth()->user()->openRegister)->id;

        CashMovement::create([
            'tenant_id' => auth()->user()->tenant_id ?? null,
            'register_session_id' => $sessionId,
            'user_id' => auth()->id(),
            'type' => $request->type,
            'amount' => (float) $request->amount,
            'note' => $request->note
        ]);

        return response()->json(['success' => true]);
    }
}