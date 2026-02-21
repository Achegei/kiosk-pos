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
            'register_session_id' => $session->id,
            'redirect' => route('dashboard') // <-- add this
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
        $request->validate([
            'closing_cash' => 'required|numeric|min:0',
        ]);

        $openRegister = RegisterSession::where('user_id', auth()->id())
            ->where('status','open')
            ->latest()
            ->first();

        if(!$openRegister){
            return response()->json([
                'success'=>false,
                'message'=>'No open register found'
            ]);
        }

        // ✅ Calculate totals properly
        $cashSales = $openRegister->transactions()
            ->whereIn('payment_method',['Cash','Mpesa'])   // MPESA treated as received money
            ->sum('total_amount');

        $expectedCash = $openRegister->opening_cash + $cashSales;

        $openRegister->update([
            'closing_cash'=>$request->closing_cash,
            'closed_at'=>now(),
            'status'=>'closed',
        ]);

        return response()->json([
            'success'=>true,
            'expected'=>$expectedCash,
            'message'=>'Register closed successfully',
            'redirect'=>route('dashboard')
        ]);
    }

}
