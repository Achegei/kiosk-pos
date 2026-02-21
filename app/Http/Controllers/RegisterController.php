<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegisterSession;

class RegisterController extends Controller
{
    // Show the open register form (optional, if you ever need it)
    public function openForm()
    {
        return view('register.open');
    }

    // Open register via AJAX
    public function open(Request $request)
    {
        $request->validate([
            'opening_cash' => 'required|numeric|min:0',
        ]);

        $session = RegisterSession::create([
            'user_id' => auth()->id(),
            'opening_cash' => $request->opening_cash,
            'opened_at' => now(),
            'status' => 'open'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Register opened successfully',
            'register_session_id' => $session->id
        ]);
    }

    // Show Close Register Modal
    public function closeForm()
    {
        $session = auth()->user()->openRegister;

        $expectedCash = $session->opening_cash + $session->transactions()
            ->where('payment_method','Cash')
            ->sum('total');

        return view('register.close', compact('session','expectedCash'));
    }

    /// Close Register
    public function close(Request $request)
{
    $user = auth()->user();

    $openRegister = $user->openRegister; // your relation: hasOne/openRegister

    if (!$openRegister) {
        return redirect()->back()->with('error', 'No open register found.');
    }

    $request->validate([
        'closing_cash' => 'required|numeric|min:0',
    ]);

    $expectedCash = $openRegister->opening_cash 
        + ($openRegister->transactions()->where('payment_method','Cash')->sum('total_amount') ?? 0);

    $openRegister->update([
        'closing_cash' => $request->closing_cash,
        'closed_at' => now(),
        'status' => 'closed',
    ]);

    return redirect()->back()->with('success', 'Register closed successfully. Expected Cash: KES '.$expectedCash);
}

}
