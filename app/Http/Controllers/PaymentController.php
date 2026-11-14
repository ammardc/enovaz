<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Casaku\CasakuGateway;
use App\Services\Casaku\FakeCasakuGateway;
use App\Services\Casaku\GatewayException;
use App\Services\PaymentService;
use App\Services\QrSvg;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function show(Request $request, string $code)
    {
        $order = $this->ownedOrder($request, $code)->load(['items.product', 'payment']);

        if ($order->status !== Order::PENDING || ! $order->payment) {
            return redirect()->route('orders.show', $order->code);
        }

        $qr = QrSvg::render($order->payment->qr_string);
        $simulation = $this->simulationEnabled();

        return response()->view('payments.show', compact('order', 'qr', 'simulation'))
            ->header('Cache-Control', 'no-store, private');
    }

    public function check(Request $request, string $code, PaymentService $payments)
    {
        $order = $this->ownedOrder($request, $code)->load('payment');
        abort_unless($order->payment, 404);

        try {
            $result = $payments->verify($order->payment, 'buyer');
        } catch (GatewayException $e) {
            report($e);

            return back()->with('error', 'Gagal cek pembayaran. Coba lagi.');
        }

        return match ($result) {
            Payment::PAID => redirect()->route('orders.show', $order->code)->with('status', 'Pembayaran terverifikasi. Akunmu sudah siap.'),
            Payment::PENDING => back()->with('error', 'Pembayaran belum masuk.'),
            Payment::EXPIRED => redirect()->route('orders.show', $order->code)->with('error', 'Tagihan sudah kedaluwarsa dan stok dikembalikan.'),
            default => redirect()->route('orders.show', $order->code)->with('error', 'Pembayaran perlu diperiksa admin. Kami akan menghubungimu.'),
        };
    }

    /** Hanya mode simulasi lokal: menandai tagihan lunas di FakeCasakuGateway. */
    public function simulate(Request $request, string $code, CasakuGateway $gateway)
    {
        abort_unless($this->simulationEnabled() && $gateway instanceof FakeCasakuGateway, 404);

        $order = $this->ownedOrder($request, $code)->load('payment');
        abort_unless($order->payment && $order->status === Order::PENDING, 404);

        $gateway->markPaid($order->payment->gateway_transaction_id);

        return back()->with('status', 'Simulasi: pembayaran '.rupiah($order->payment->expected_amount).' terkirim. Sekarang tekan "Cek status pembayaran".');
    }

    /**
     * Webhook Casaku. Tanda tangan HMAC-SHA256 atas body mentah diverifikasi dulu,
     * lalu status tetap dicek ulang ke API Casaku sebelum stok diserahkan.
     */
    public function webhook(Request $request, PaymentService $payments)
    {
        $raw = $request->getContent();
        $secret = (string) config('services.casaku.webhook_secret');
        $signature = (string) $request->header('x-casaku-signature', '');

        if ($secret === '' || ! hash_equals(hash_hmac('sha256', $raw, $secret), $signature)) {
            return response()->json(['status' => 'invalid_signature'], 401);
        }

        $data = json_decode($raw, true);
        $payment = is_array($data) && isset($data['transactionId'])
            ? Payment::where('gateway_transaction_id', (string) $data['transactionId'])->first()
            : null;

        if ($payment) {
            if (isset($data['amount']) && (int) $data['amount'] !== $payment->expected_amount) {
                $payments->flagReview($payment, 'webhook_amount_mismatch');
            } else {
                try {
                    $payments->verify($payment, 'webhook');
                } catch (GatewayException $e) {
                    report($e);
                }
            }
        }

        return response()->json(['status' => 'success']);
    }

    private function simulationEnabled(): bool
    {
        return config('services.casaku.driver') !== 'live' && app()->environment(['local', 'testing']);
    }
}
