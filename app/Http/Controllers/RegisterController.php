<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegisterSession;
use App\Models\Transaction;
use App\Models\CashMovement;

class RegisterController extends Controller
{

    private function tenantId()
    {
        return auth()->user()->tenant_id;
    }

    // OPEN REGISTER
    public function open(Request $request)
    {
        $request->validate([
            'opening_cash' => 'required|numeric|min:0',
        ]);

        $session = RegisterSession::create([
            'tenant_id' => $this->tenantId(),
            'user_id' => auth()->id(),
            'opening_cash' => $request->opening_cash,
            'opened_at' => now(),
            'status' => 'open'
        ]);

        return response()->json([
            'success'=>true,
            'register_session_id'=>$session->id,
            'redirect'=>route('dashboard')
        ]);
    }

    // CLOSE FORM
    public function closeForm()
    {
        $session = RegisterSession::where('tenant_id',$this->tenantId())
            ->where('user_id',auth()->id())
            ->where('status','open')
            ->firstOrFail();

        $cashSales = $session->transactions()
            ->where('tenant_id',$this->tenantId())
            ->where('payment_method','Cash')
            ->sum('total_amount');

        $movements = CashMovement::where('tenant_id',$this->tenantId())
            ->where('register_session_id',$session->id)
            ->selectRaw("
                SUM(CASE WHEN type='drop' THEN amount ELSE 0 END) drops,
                SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) expenses,
                SUM(CASE WHEN type='payout' THEN amount ELSE 0 END) payouts,
                SUM(CASE WHEN type='deposit' THEN amount ELSE 0 END) deposits,
                SUM(CASE WHEN type='adjustment' THEN amount ELSE 0 END) adjustments
            ")
            ->first();

        $expectedCash = $session->calculateExpectedCash();

        return view('register.close',[
            'session'=>$session,
            'expectedCash'=>$expectedCash,
            'cashSales'=>$cashSales,
            'drops'=>$movements->drops ?? 0,
            'expenses'=>$movements->expenses ?? 0,
            'payouts'=>$movements->payouts ?? 0,
            'deposits'=>$movements->deposits ?? 0,
            'adjustments'=>$movements->adjustments ?? 0,
        ]);
    }


    // CLOSE REGISTER
public function close(Request $request)
{
    $request->validate([
        'closing_cash'=>'required|numeric|min:0'
    ]);

    $tenant = $this->tenantId();

    $session = RegisterSession::where('tenant_id', $tenant)
        ->where('status', 'open')
        ->where('user_id', auth()->id())
        ->firstOrFail();

    // ONE QUERY SALES (FAST)
    $sales = Transaction::where('tenant_id', $tenant)
        ->where('register_session_id', $session->id)
        ->selectRaw("
            SUM(CASE WHEN payment_method='Cash' THEN total_amount ELSE 0 END) cash,
            SUM(CASE WHEN payment_method='Mpesa' THEN total_amount ELSE 0 END) mpesa,
            SUM(CASE WHEN payment_method='Credit' THEN total_amount ELSE 0 END) credit
        ")->first();

    // ONE QUERY MOVEMENTS (FAST)
    $movements = CashMovement::where('tenant_id', $tenant)
        ->where('register_session_id', $session->id)
        ->selectRaw("
            SUM(CASE WHEN type='drop' THEN amount ELSE 0 END) drops,
            SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) expenses,
            SUM(CASE WHEN type='payout' THEN amount ELSE 0 END) payouts,
            SUM(CASE WHEN type='deposit' THEN amount ELSE 0 END) deposits,
            SUM(CASE WHEN type='adjustment' THEN amount ELSE 0 END) adjustments
        ")->first();

    $expectedCash = $session->calculateExpectedCash($tenant);
    $difference = $request->closing_cash - $expectedCash;

    $session->update([
        'closing_cash' => $request->closing_cash,
        'closed_at' => now(),
        'status' => 'closed',
        'cash_sales' => $sales->cash ?? 0,
        'mpesa_sales' => $sales->mpesa ?? 0,
        'credit_sales' => $sales->credit ?? 0,
        'difference' => $difference,
        'cash_drops' => $movements->drops ?? 0,
        'cash_expenses' => $movements->expenses ?? 0,
        'cash_payouts' => $movements->payouts ?? 0,
        'cash_deposits' => $movements->deposits ?? 0,
        'cash_adjustments' => $movements->adjustments ?? 0,
    ]);

    // ✅ Return report for JS
    return response()->json([
        'success' => true,
        'report' => [
            'cashier' => $session->user->name,
            'user_id' => $session->user_id,
            'session_id' => $session->id,
            'opened_at' => $session->opened_at,
            'closed_at' => $session->closed_at,
            'opening_cash' => $session->opening_cash,
            'cash_sales' => $session->cash_sales,
            'mpesa_sales' => $session->mpesa_sales,
            'credit_sales' => $session->credit_sales,
            'expected_cash' => $expectedCash,
            'counted_cash' => $request->closing_cash,
            'drops' => $movements->drops ?? 0,
            'expenses' => $movements->expenses ?? 0,
            'payouts' => $movements->payouts ?? 0,
            'deposits' => $movements->deposits ?? 0,
            'adjustments' => $movements->adjustments ?? 0,
        ]
    ]);
}

    // TOTALS ENDPOINT (TENANT SAFE)
    public function totals($id)
    {
        $tenant = $this->tenantId();

        $session = RegisterSession::where('tenant_id',$tenant)->findOrFail($id);

        $sales = Transaction::where('tenant_id',$tenant)
            ->where('register_session_id',$id)
            ->selectRaw("
                SUM(CASE WHEN payment_method='Cash' THEN total_amount ELSE 0 END) cash,
                SUM(CASE WHEN payment_method='Mpesa' THEN total_amount ELSE 0 END) mpesa,
                SUM(CASE WHEN payment_method='Credit' THEN total_amount ELSE 0 END) credit
            ")->first();

        $movements = CashMovement::where('tenant_id',$tenant)
            ->where('register_session_id',$id)
            ->selectRaw("
                SUM(CASE WHEN type='drop' THEN amount ELSE 0 END) drops,
                SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) expenses,
                SUM(CASE WHEN type='payout' THEN amount ELSE 0 END) payouts,
                SUM(CASE WHEN type='deposit' THEN amount ELSE 0 END) deposits,
                SUM(CASE WHEN type='adjustment' THEN amount ELSE 0 END) adjustments
            ")->first();

        return response()->json([
            'openingCash'=>$session->opening_cash,
            'cash'=>$sales->cash ?? 0,
            'mpesa'=>$sales->mpesa ?? 0,
            'credit'=>$sales->credit ?? 0,
            'drops'=>$movements->drops ?? 0,
            'expenses'=>$movements->expenses ?? 0,
            'payouts'=>$movements->payouts ?? 0,
            'deposits'=>$movements->deposits ?? 0,
            'adjustments'=>$movements->adjustments ?? 0,
        ]);
    }


    // CLOSE DATA (FIXED TENANT BUG HERE 🔥)
    public function closeData()
    {
        $tenant = $this->tenantId();

        $session = RegisterSession::where('tenant_id',$tenant)
            ->where('user_id',auth()->id())
            ->where('status','open')
            ->first();

        if(!$session){
            return response()->json([
                'cashSales'=>0,
                'drops'=>0,
                'payouts'=>0,
                'expectedCash'=>0
            ]);
        }

        $cashSales = Transaction::where('tenant_id',$tenant)
            ->where('register_session_id',$session->id)
            ->where('payment_method','Cash')
            ->sum('total_amount');

        $drops = CashMovement::where('tenant_id',$tenant)
            ->where('register_session_id',$session->id)
            ->where('type','drop')
            ->sum('amount');

        $payouts = CashMovement::where('tenant_id',$tenant)
            ->where('register_session_id',$session->id)
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