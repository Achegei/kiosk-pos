<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegisterSession;
use App\Models\Transaction;
use App\Models\CashMovement;

class RegisterController extends Controller
{
    // Show open register form
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
            'redirect' => route('dashboard')
        ]);
    }

    // Show Close Register Modal
    public function closeForm()
{
    $session = auth()->user()->openRegister;

    if(!$session){
        abort(404, 'No open register');
    }

    // Sales totals
    $cashSales = $session->transactions()
        ->where('payment_method', 'Cash')
        ->sum('total_amount');

    // Cash movements sums directly from DB (reliable)
    $drops       = CashMovement::where('register_session_id', $session->id)
                    ->where('tenant_id', auth()->user()->tenant_id)
                    ->where('type', 'drop')
                    ->sum('amount');

    $expenses    = CashMovement::where('register_session_id', $session->id)
                    ->where('tenant_id', auth()->user()->tenant_id)
                    ->where('type', 'expense')
                    ->sum('amount');

    $payouts     = CashMovement::where('register_session_id', $session->id)
                    ->where('tenant_id', auth()->user()->tenant_id)
                    ->where('type', 'payout')
                    ->sum('amount');

    $deposits    = CashMovement::where('register_session_id', $session->id)
                    ->where('tenant_id', auth()->user()->tenant_id)
                    ->where('type', 'deposit')
                    ->sum('amount');

    $adjustments = CashMovement::where('register_session_id', $session->id)
                    ->where('tenant_id', auth()->user()->tenant_id)
                    ->where('type', 'adjustment')
                    ->sum('amount');

    // Expected cash
    $expectedCash = $session->calculateExpectedCash();

    return view('register.close', compact(
        'session',
        'expectedCash',
        'cashSales',
        'drops',
        'expenses',
        'payouts',
        'deposits',
        'adjustments'
    ));
}

    // Close register
    public function close(Request $request)
    {
        $request->validate([
            'closing_cash' => 'required|numeric|min:0',
        ]);

        $session = RegisterSession::where('status','open')
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // Sales totals
        $cashSales   = Transaction::where('register_session_id', $session->id)
            ->where('payment_method','Cash')
            ->sum('total_amount');
        $mpesaSales  = Transaction::where('register_session_id', $session->id)
            ->where('payment_method','Mpesa')
            ->sum('total_amount');
        $creditSales = Transaction::where('register_session_id', $session->id)
            ->where('payment_method','Credit')
            ->sum('total_amount');

        // Cash movements totals
        $movements = CashMovement::where('register_session_id', $session->id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->get();

        $drops       = $movements->where('type','drop')->sum('amount');
        $expenses    = $movements->where('type','expense')->sum('amount');
        $payouts     = $movements->where('type','payout')->sum('amount');
        $deposits    = $movements->where('type','deposit')->sum('amount');
        $adjustments = $movements->where('type','adjustment')->sum('amount');

        // Expected cash calculation
        $expectedCash = $session->calculateExpectedCash();

        $difference = $request->closing_cash - $expectedCash;

        // Update register session
        $session->update([
            'closing_cash'    => $request->closing_cash,
            'closed_at'       => now(),
            'status'          => 'closed',
            'cash_sales'      => $cashSales,
            'mpesa_sales'     => $mpesaSales,
            'credit_sales'    => $creditSales,
            'difference'      => $difference,
            'cash_drops'      => $drops,
            'cash_expenses'   => $expenses,
            'cash_payouts'    => $payouts,
            'cash_deposits'   => $deposits,
        ]);

        // OPTIONAL: Reset all relevant totals for staff after closing
        if(auth()->user()->role === 'staff') {
            auth()->user()->update([
                'open_register_id' => null, // or any field tracking open register
            ]);

            // Optionally reset movements or cached totals
            // CashMovement::where('register_session_id', $session->id)->delete(); // If you want to archive separately
        }

        return response()->json([
            'success' => true,
            'report' => [
                'cashier'       => auth()->user()->name,
                'user_id'       => auth()->id(),
                'session_id'    => $session->id,
                'opening_cash'  => $session->opening_cash,
                'cash_sales'    => $cashSales,
                'mpesa_sales'   => $mpesaSales,
                'credit_sales'  => $creditSales,
                'cash_drops'    => $drops,
                'expenses'      => $expenses,
                'payouts'       => $payouts,
                'deposits'      => $deposits,
                'expected_cash' => $expectedCash,
                'counted_cash'  => $request->closing_cash,
                'difference'    => $difference,
                'opened_at'     => $session->opened_at,
                'closed_at'     => $session->closed_at,
            ]
        ]);
    }

    // Movements summary endpoint
    public function movements($id)
    {
        $register = RegisterSession::findOrFail($id);
        $summary = $register->cashMovementsSummary();
        return response()->json($summary);
    }

    public function totals($id)
{
    $session = RegisterSession::findOrFail($id);
    $movements = $session->cashMovements()->get();

    $cash = $session->transactions()->where('payment_method','Cash')->sum('total_amount');
    $mpesa = $session->transactions()->where('payment_method','Mpesa')->sum('total_amount');
    $credit = $session->transactions()->where('payment_method','Credit')->sum('total_amount');

    $drops       = $movements->where('type','drop')->sum('amount');
    $expenses    = $movements->where('type','expense')->sum('amount');
    $payouts     = $movements->where('type','payout')->sum('amount');
    $deposits    = $movements->where('type','deposit')->sum('amount');
    $adjustments = $movements->where('type','adjustment')->sum('amount');

    $expectedCash = $session->opening_cash + $cash - $drops - $expenses - $payouts + $deposits + $adjustments;

    return response()->json([
    'openingCash' => $session->opening_cash,

    'cash'   => $session->transactions()->where('payment_method','Cash')->sum('total_amount'),
    'mpesa'  => $session->transactions()->where('payment_method','Mpesa')->sum('total_amount'),
    'credit' => $session->transactions()->where('payment_method','Credit')->sum('total_amount'),

    'drops'       => $movements->where('type','drop')->sum('amount'),
    'expenses'    => $movements->where('type','expense')->sum('amount'),
    'payouts'     => $movements->where('type','payout')->sum('amount'),
    'deposits'    => $movements->where('type','deposit')->sum('amount'),
    'adjustments' => $movements->where('type','adjustment')->sum('amount'),
]);
}

public function closeData()
{
    $session = auth()->user()->openRegister;

    if(!$session){
        return response()->json([
            'cashSales'=>0,
            'drops'=>0,
            'payouts'=>0,
            'expectedCash'=>0
        ]);
    }

    $cashSales = Sale::where('register_session_id',$session->id)
        ->where('payment_method','cash')
        ->sum('total');

    $drops = CashMovement::where('register_session_id',$session->id)
        ->where('type','drop')
        ->sum('amount');

    $payouts = CashMovement::where('register_session_id',$session->id)
        ->whereIn('type',['expense','payout'])
        ->sum('amount');

    $expectedCash = $session->opening_cash + $cashSales - $drops - $payouts;

    return response()->json([
        'cashSales'=>$cashSales,
        'drops'=>$drops,
        'payouts'=>$payouts,
        'expectedCash'=>$expectedCash
    ]);
}

}
