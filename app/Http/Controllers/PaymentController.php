<?php

namespace App\Http\Controllers;

use App\Services\CasakuClient;
use App\Services\PaymentSettlement;
use App\Jobs\ProcessCasakuPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function show(string $reference)
    {
        $invoice = DB::table('payment_invoices')->where('reference', $reference)->where('user_id', auth()->id())->first();
        abort_unless($invoice, 404);
        return view('payments.show', compact('invoice'));
    }

    public function check(string $reference, CasakuClient $casaku, PaymentSettlement $settlement)
    {
        $invoice = DB::table('payment_invoices')->where('reference', $reference)->where('user_id', auth()->id())->first();
        abort_unless($invoice, 404);
        if (! $invoice->provider_transaction_id || ! in_array($invoice->status, ['pending','review'], true)) return back();
        try {
            $status = $casaku->status($invoice->provider_transaction_id);
            if (data_get($status, 'status') === 'paid') {
                $settlement->paid($invoice->provider_transaction_id, (int) data_get($status, 'amount'), data_get($status, 'paidAt'), 'check:'.hash('sha256', json_encode($status)));
            } elseif (in_array(data_get($status, 'status'), ['expired','cancel'], true)) {
                $settlement->expired($invoice->provider_transaction_id);
            }
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['payment' => 'Status belum bisa dipastikan. Silakan coba lagi nanti.']);
        }
        return back()->with('success', 'Status terbaru sudah diperiksa.');
    }

    public function webhook(Request $request)
    {
        $secret = config('store.casaku_secret');
        $signature = $request->header('X-Casaku-Signature');
        $raw = $request->getContent();
        if (! $secret || ! is_string($signature) || ! ctype_xdigit($signature) || strlen($signature) !== 64 ||
            ! hash_equals(hash_hmac('sha256', $raw, $secret), strtolower($signature))) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }
        $payload = json_decode($raw, true);
        if (! is_array($payload) || ! is_string($payload['transactionId'] ?? null) ||
            ($payload['status'] ?? null) !== 'paid' || ! is_int($payload['amount'] ?? null)) {
            return response()->json(['error' => 'Invalid payload'], 422);
        }
        ProcessCasakuPayment::dispatch($payload['transactionId'], $payload['amount'], $payload['paidAt'] ?? null, hash('sha256', $raw));
        return response()->json(['ok' => true]);
    }
}
