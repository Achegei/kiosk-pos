<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use Illuminate\Http\Request;

class CashMovementController extends Controller
{
    public function store(Request $request)
{
    try {
        // -------------------------
        // Validate input
        // -------------------------
        $request->validate([
            'register_session_id' => 'nullable|exists:register_sessions,id',
            'type' => 'required|in:drop,expense,payout,deposit,adjustment',
            'amount' => 'required|numeric|min:0.01',
            'note' => 'nullable|string|max:255'
        ]);

        // -------------------------
        // Ensure user is logged in
        // -------------------------
        if (!auth()->check()) {
            \Log::warning('Unauthorized cash movement attempt', [
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // -------------------------
        // Attach open register if not provided
        // -------------------------
        $sessionId = $request->register_session_id 
            ?? optional(auth()->user()->openRegister)->id;

        if (!$sessionId) {
            throw new \Exception('No active register session found for this user.');
        }

        // -------------------------
        // Create Cash Movement
        // -------------------------
        $cashMovement = CashMovement::create([
            'tenant_id' => auth()->user()->tenant_id ?? null,
            'register_session_id' => $sessionId,
            'user_id' => auth()->id(),
            'type' => $request->type,
            'amount' => (float) $request->amount,
            'note' => $request->note
        ]);

        return response()->json([
            'success' => true,
            'data' => $cashMovement
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        // -------------------------
        // Validation errors
        // -------------------------
        \Log::info('CashMovement validation failed', [
            'user_id' => auth()->id(),
            'errors' => $e->errors(),
            'request' => $request->all()
        ]);

        return response()->json([
            'success' => false,
            'message' => $e->errors()
        ], 422);

    } catch (\Throwable $e) {
        // -------------------------
        // Unexpected errors
        // -------------------------
        \Log::error('CashMovement creation failed', [
            'user_id' => auth()->id(),
            'request' => $request->all(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to create cash movement. Please try again.'
        ], 500);
    }
}
}