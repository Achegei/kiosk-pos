<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegisterSession;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;


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

    $openRegister = RegisterSession::where('status','open')
        ->where('user_id',auth()->id())
        ->first();

    if(!$openRegister){
        return response()->json([
            'success'=>false,
            'message'=>'No open register found'
        ]);
    }

    // ⭐ VERY IMPORTANT → USE register_session_id ONLY
    $cashSales = Transaction::where('register_session_id',$openRegister->id)
        ->where('payment_method','Cash')
        ->sum('total_amount');

    $mpesaSales = Transaction::where('register_session_id',$openRegister->id)
        ->where('payment_method','Mpesa')
        ->sum('total_amount');

    $creditSales = Transaction::where('register_session_id',$openRegister->id)
        ->where('payment_method','Credit')
        ->sum('total_amount');

    $expectedCash = $openRegister->opening_cash + $cashSales + $mpesaSales;
    $difference = $request->closing_cash - $expectedCash;

    $openRegister->update([
        'closing_cash'=>$request->closing_cash,
        'closed_at'=>now(),
        'status'=>'closed',
        'cash_sales'=>$cashSales,
        'mpesa_sales'=>$mpesaSales,
        'credit_sales'=>$creditSales,
        'difference'=>$difference,
    ]);

    return response()->json([
        'success'=>true,
        'report'=>[
            'cashier'=>auth()->user()->name,
            'user_id'       => auth()->user()->id,
            'session_id'    => $openRegister->id,
            'opening_cash'=>$openRegister->opening_cash,
            'cash_sales'=>$cashSales,
            'mpesa_sales'=>$mpesaSales,
            'credit_sales'=>$creditSales,
            'expected_cash'=>$expectedCash,
            'counted_cash'=>$request->closing_cash,
            'difference'=>$difference,
            'opened_at'=>$openRegister->opened_at,
            'closed_at'=>$openRegister->closed_at,
        ]
    ]);
}

}
