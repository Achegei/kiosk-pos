<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegisterSession;
use App\Models\Transaction;
use App\Models\CashMovement;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class RegisterController extends Controller
{

    private function tenantId()
    {
        return auth()->user()->tenant_id ?? null;
    }

    // OPEN REGISTER
    public function open(Request $request)
    {
        $request->validate([
            'opening_cash' => 'required|numeric|min:0',
        ]);

        try {
            $session = RegisterSession::create([
                'tenant_id' => $this->tenantId(),
                'user_id' => auth()->id(),
                'opening_cash' => $request->opening_cash,
                'opened_at' => now(),
                'status' => 'open'
            ]);

            return response()->json([
                'success' => true,
                'register_session_id' => $session->id,
                'redirect' => route('dashboard')
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to open register', [
                'tenant' => $this->tenantId(),
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to open register'
            ], 500);
        }
    }

    // CLOSE FORM
     public function closeForm()
    {
        try {
            $tenant = $this->tenantId();
            $session = RegisterSession::where('tenant_id', $tenant)
                ->where('user_id', auth()->id())
                ->where('status', 'open')
                ->firstOrFail();

            $cashSales = $session->transactions()
                ->where('tenant_id', $tenant)
                ->where('payment_method','Cash')
                ->sum('total_amount');

            $movements = CashMovement::where('tenant_id', $tenant)
                ->where('register_session_id', $session->id)
                ->selectRaw("
                    SUM(CASE WHEN type='drop' THEN amount ELSE 0 END) drops,
                    SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) expenses,
                    SUM(CASE WHEN type='payout' THEN amount ELSE 0 END) payouts,
                    SUM(CASE WHEN type='deposit' THEN amount ELSE 0 END) deposits,
                    SUM(CASE WHEN type='adjustment' THEN amount ELSE 0 END) adjustments
                ")->first();

            $expectedCash = $session->calculateExpectedCash();

            return view('register.close', [
                'session' => $session,
                'expectedCash' => $expectedCash,
                'cashSales' => $cashSales,
                'drops' => $movements->drops ?? 0,
                'expenses' => $movements->expenses ?? 0,
                'payouts' => $movements->payouts ?? 0,
                'deposits' => $movements->deposits ?? 0,
                'adjustments' => $movements->adjustments ?? 0,
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to load close form', [
                'tenant' => $this->tenantId(),
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            abort(500, 'Unable to load register close form');
        }
    }

public function close(Request $request)
{
    $request->validate([
        'closing_cash' => 'required|numeric|min:0'
    ]);

    try {

        $session = RegisterSession::where('user_id', auth()->id())
            ->where('status', 'open')
            ->firstOrFail();

        // ---------------- SALES TOTALS ----------------
        $sales = $session->transactions()
            ->selectRaw("
                COALESCE(SUM(CASE WHEN payment_method='Cash' THEN total_amount END),0) AS cash,
                COALESCE(SUM(CASE WHEN payment_method='Mpesa' THEN total_amount END),0) AS mpesa,
                COALESCE(SUM(CASE WHEN payment_method='Credit' THEN total_amount END),0) AS credit
            ")->first();

        // ---------------- CASH MOVEMENTS ----------------
        $movements = CashMovement::where('register_session_id', $session->id)
            ->where('tenant_id', $session->tenant_id)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN type='drop' THEN amount END),0) AS drops,
                COALESCE(SUM(CASE WHEN type='expense' THEN amount END),0) AS expenses,
                COALESCE(SUM(CASE WHEN type='payout' THEN amount END),0) AS payouts,
                COALESCE(SUM(CASE WHEN type='deposit' THEN amount END),0) AS deposits,
                COALESCE(SUM(CASE WHEN type='adjustment' THEN amount END),0) AS adjustments
            ")->first();

        $cashDrops       = $movements->drops;
        $cashExpenses    = $movements->expenses;
        $cashPayouts     = $movements->payouts;
        $cashDeposits    = $movements->deposits;
        $cashAdjustments = $movements->adjustments;

        // ---------------- EXPECTED CASH ----------------
        $expectedCash =
            $session->opening_cash
            + $sales->cash
            + $cashDeposits
            + $cashAdjustments
            - $cashDrops
            - $cashExpenses
            - $cashPayouts;

        $difference = $request->closing_cash - $expectedCash;

        // ---------------- UPDATE SESSION ----------------
        $session->update([
            'closing_cash'     => $request->closing_cash,
            'closed_at'        => now(),
            'status'           => 'closed',
            'cash_sales'       => $sales->cash,
            'mpesa_sales'      => $sales->mpesa,
            'credit_sales'     => $sales->credit,
            'difference'       => $difference,
            'cash_drops'       => $cashDrops,
            'cash_expenses'    => $cashExpenses,
            'cash_payouts'     => $cashPayouts,
            'cash_deposits'    => $cashDeposits,
            'cash_adjustments' => $cashAdjustments,
        ]);

        // ✅ RETURN REDIRECT TO PDF ROUTE
        return response()->json([
            'success'  => true,
            'redirect' => route('register.print', $session->id)
        ]);

    } catch (\Throwable $e) {

        Log::error('Failed to close register', [
            'user_id' => auth()->id(),
            'error'   => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to close register'
        ], 500);
    }
}
    // TOTALS ENDPOINT (TENANT SAFE)
     public function totals($id)
    {
        try {
            $tenant = $this->tenantId();

            $session = RegisterSession::where('tenant_id', $tenant)->findOrFail($id);

            $sales = Transaction::where('tenant_id', $tenant)
                ->where('register_session_id', $id)
                ->selectRaw("
                    SUM(CASE WHEN payment_method='Cash' THEN total_amount ELSE 0 END) cash,
                    SUM(CASE WHEN payment_method='Mpesa' THEN total_amount ELSE 0 END) mpesa,
                    SUM(CASE WHEN payment_method='Credit' THEN total_amount ELSE 0 END) credit
                ")->first();

            $movements = CashMovement::where('tenant_id', $tenant)
                ->where('register_session_id', $id)
                ->selectRaw("
                    SUM(CASE WHEN type='drop' THEN amount ELSE 0 END) drops,
                    SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) expenses,
                    SUM(CASE WHEN type='payout' THEN amount ELSE 0 END) payouts,
                    SUM(CASE WHEN type='deposit' THEN amount ELSE 0 END) deposits,
                    SUM(CASE WHEN type='adjustment' THEN amount ELSE 0 END) adjustments
                ")->first();

            return response()->json([
                'openingCash' => $session->opening_cash,
                'cash' => $sales->cash ?? 0,
                'mpesa' => $sales->mpesa ?? 0,
                'credit' => $sales->credit ?? 0,
                'drops' => $movements->drops ?? 0,
                'expenses' => $movements->expenses ?? 0,
                'payouts' => $movements->payouts ?? 0,
                'deposits' => $movements->deposits ?? 0,
                'adjustments' => $movements->adjustments ?? 0,
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to fetch register totals', [
                'tenant' => $this->tenantId(),
                'session_id' => $id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch totals'
            ], 500);
        }
    }


    // CLOSE DATA (FIXED TENANT BUG HERE 🔥)
    public function closeData()
    {
        try {
            $tenant = $this->tenantId();

            $session = RegisterSession::where('tenant_id', $tenant)
                ->where('user_id', auth()->id())
                ->where('status','open')
                ->first();

            if (!$session) {
                return response()->json([
                    'cashSales' => 0,
                    'drops' => 0,
                    'payouts' => 0,
                    'expectedCash' => 0
                ]);
            }

            $cashSales = Transaction::where('tenant_id', $tenant)
                ->where('register_session_id', $session->id)
                ->where('payment_method','Cash')
                ->sum('total_amount');

            $drops = CashMovement::where('tenant_id', $tenant)
                ->where('register_session_id', $session->id)
                ->where('type','drop')
                ->sum('amount');

            $payouts = CashMovement::where('tenant_id', $tenant)
                ->where('register_session_id', $session->id)
                ->whereIn('type',['expense','payout'])
                ->sum('amount');

            $expectedCash = $session->opening_cash + $cashSales - $drops - $payouts;

            return response()->json([
                'cashSales' => $cashSales,
                'drops' => $drops,
                'payouts' => $payouts,
                'expectedCash' => $expectedCash
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to fetch close data', [
                'tenant' => $this->tenantId(),
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch close data'
            ], 500);
        }
    }

    public function printEndOfDay($id)
{
    $tenantId = $this->tenantId();

    $session = RegisterSession::where('tenant_id', $tenantId)
        ->where('user_id', auth()->id()) //user owns this session
        ->where('status', 'closed')
        ->findOrFail($id);

    // 🔥 Get Tenant (Store Info)
    $tenant = \App\Models\Tenant::findOrFail($tenantId);

    // SALES
    $sales = Transaction::where('tenant_id', $tenantId)
        ->where('register_session_id', $session->id)
        ->selectRaw("
            COALESCE(SUM(CASE WHEN payment_method='Cash' THEN total_amount END),0) cash,
            COALESCE(SUM(CASE WHEN payment_method='Mpesa' THEN total_amount END),0) mpesa,
            COALESCE(SUM(CASE WHEN payment_method='Credit' THEN total_amount END),0) credit
        ")
        ->first();

    // MOVEMENTS
    $movements = CashMovement::where('tenant_id', $tenantId)
        ->where('register_session_id', $session->id)
        ->selectRaw("
            COALESCE(SUM(CASE WHEN type='drop' THEN amount END),0) drops,
            COALESCE(SUM(CASE WHEN type='expense' THEN amount END),0) expenses,
            COALESCE(SUM(CASE WHEN type='payout' THEN amount END),0) payouts,
            COALESCE(SUM(CASE WHEN type='deposit' THEN amount END),0) deposits,
            COALESCE(SUM(CASE WHEN type='adjustment' THEN amount END),0) adjustments
        ")
        ->first();

    $expectedCash =
        $session->opening_cash
        + $sales->cash
        + $movements->deposits
        + $movements->adjustments
        - $movements->drops
        - $movements->expenses
        - $movements->payouts;

    $difference = $session->closing_cash - $expectedCash;

    $pdf = Pdf::loadView('reports.end_of_day', compact(
        'tenant',
        'session',
        'sales',
        'movements',
        'expectedCash',
        'difference'
    ))->setPaper([0, 0, 226, 900]); // 80mm

    return $pdf->stream("register-{$session->id}.pdf");
}

public function printLast()
{
    // Get the latest closed register
    $lastClosed = RegisterSession::where('tenant_id', auth()->user()->tenant_id)
        ->where('user_id', auth()->id())  // <-- filter by logged-in user
        ->where('status','closed')
        ->latest('closed_at')
        ->first();

    if (!$lastClosed) {
        abort(404, 'No closed register found');
    }

    // Redirect to the standard print route
    return redirect()->route('register.print', $lastClosed->id);
}


}