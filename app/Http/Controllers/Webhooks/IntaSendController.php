<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\Payment;
use App\Models\PaymentLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IntaSendController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->json()->all();

        Log::info('[IntaSend Webhook] Received', $payload);

        // Respond to IntaSend verification challenge
        $challenge = $payload['challenge'] ?? null;

        $invoiceId = $payload['invoice_id'] ?? null;
        $apiRef    = $payload['api_ref'] ?? null;
        $state     = strtolower($payload['state'] ?? 'pending');
        $amount    = $payload['net_amount'] ?? $payload['value'] ?? 0;

        if (!$apiRef) {
            Log::warning('[IntaSend] Missing api_ref', $payload);
            return response()->json(['success' => true]);
        }

        /*
        |--------------------------------------------------------------------------
        | Resolve tenant from api_ref
        |--------------------------------------------------------------------------
        */

        $tenant = null;

        if (preg_match('/saas-tenant(\d+)-payment/', $apiRef, $matches)) {
            $tenant = Tenant::find($matches[1]);
        }

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | 1️⃣ Store webhook log
            |--------------------------------------------------------------------------
            */

            PaymentLog::create([
                'tenant_id'  => $tenant->id ?? null,
                'invoice_id' => $invoiceId,
                'api_ref'    => $apiRef,
                'status'     => strtoupper($state),
                'payload'    => json_encode($payload),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | 2️⃣ Find existing payment (using api_ref ONLY)
            |--------------------------------------------------------------------------
            */

            $payment = Payment::where('api_ref', $apiRef)->first();

            if (!$payment) {

                Log::error('[IntaSend] Payment not found', [
                    'api_ref' => $apiRef,
                    'invoice_id' => $invoiceId
                ]);

                DB::commit();
                return response()->json(['success' => true]);
            }

            Log::info('[IntaSend] Matching payment found', [
                'payment_id' => $payment->id,
                'status' => $payment->status
            ]);

            /*
            |--------------------------------------------------------------------------
            | 3️⃣ Handle successful payment
            |--------------------------------------------------------------------------
            */

            if (in_array($state, ['complete', 'paid']) && $payment->status !== 'paid') {

                $payment->update([
                    'status'  => 'paid',
                    'payload' => json_encode($payload),
                ]);

                Log::info('[IntaSend] Payment marked as PAID', [
                    'payment_id' => $payment->id
                ]);

                /*
                |--------------------------------------------------------------------------
                | 4️⃣ Activate tenant subscription
                |--------------------------------------------------------------------------
                */

                if ($tenant) {

                    $tenant->subscription_status = 'active';

                    $tenant->expiry_date =
                        $tenant->expiry_date && $tenant->expiry_date->isFuture()
                        ? $tenant->expiry_date->addMonth()
                        : now()->addMonth();

                    $tenant->save();

                    Log::info('[IntaSend] Tenant subscription unlocked', [
                        'tenant_id' => $tenant->id,
                        'new_expiry' => $tenant->expiry_date
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 5️⃣ Handle failed payment
            |--------------------------------------------------------------------------
            */

            if (in_array($state, ['failed', 'cancelled'])) {

                $payment->update([
                    'status'  => 'failed',
                    'payload' => json_encode($payload),
                ]);

                Log::warning('[IntaSend] Payment failed', [
                    'payment_id' => $payment->id
                ]);
            }

            DB::commit();

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('[IntaSend] Webhook processing error', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);
        }

        return response()->json(['success' => true]);
    }
}