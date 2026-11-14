<?php

namespace App\Services\Casaku;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Adapter API Casaku (https://api.casaku.id), autentikasi header x-license-key.
 * License Key hanya ada di backend, tidak pernah dikirim ke browser atau ditulis ke log.
 *
 * Catatan: field respons mengikuti dokumentasi publik Casaku (data.transactionId, data.totalAmount,
 * data.qr_string, data.status, data.paidAt). Cocokkan dengan respons asli akun merchant sebelum live.
 */
class HttpCasakuGateway implements CasakuGateway
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $licenseKey,
        private readonly string $qrId,
        private readonly array $packageIds,
        private readonly string $prefix,
    ) {
    }

    public function createQris(int $baseAmount, int $expiresMinutes): array
    {
        // Tidak di-retry: pengulangan POST bisa membuat tagihan ganda.
        $res = $this->send(fn (PendingRequest $http) => $http->post('/api/generate/v2/qris', [
            'qr_id' => $this->qrId,
            'qrType' => 'dynamic',
            'paymentMethod' => 'qris',
            'amount' => $baseAmount,
            'useUniqueCode' => true,
            'packageIds' => $this->packageIds,
            'expiredInMinutes' => $expiresMinutes,
            'prefix' => substr($this->prefix, 0, 8),
            'useQris' => true,
        ]));

        $data = (array) $res->json('data', []);
        $id = $data['transactionId'] ?? null;
        $total = $data['totalAmount'] ?? null;
        $qr = $data['qr_string'] ?? null;

        if (! is_string($id) || $id === '' || ! is_numeric($total) || ! is_string($qr) || $qr === '') {
            throw new GatewayException('Respons pembuatan tagihan Casaku tidak lengkap.');
        }

        $total = (int) $total;
        if ($total < $baseAmount || $total > $baseAmount + 999) {
            throw new GatewayException('Nominal tagihan dari Casaku di luar rentang kode unik.');
        }

        return ['transaction_id' => $id, 'total_amount' => $total, 'qr_string' => $qr];
    }

    public function checkStatus(string $transactionId): array
    {
        $res = $this->send(fn (PendingRequest $http) => $http->retry(2, 400, throw: false)
            ->post('/api/generate/check-status', ['transactionId' => $transactionId]));

        $data = (array) $res->json('data', []);
        $status = strtolower((string) ($data['status'] ?? ''));

        if (! in_array($status, ['pending', 'paid', 'cancel', 'expired'], true)) {
            throw new GatewayException('Status transaksi dari Casaku tidak dikenal.');
        }

        return [
            'transaction_id' => isset($data['transactionId']) ? (string) $data['transactionId'] : null,
            'status' => $status,
            'paid_at' => ! empty($data['paidAt']) ? Carbon::parse($data['paidAt'])->utc() : null,
        ];
    }

    public function cancel(string $transactionId): void
    {
        $this->send(fn (PendingRequest $http) => $http->post('/api/generate/cancel-status', ['transactionId' => $transactionId]));
    }

    private function send(callable $call): Response
    {
        if ($this->licenseKey === '' || $this->qrId === '') {
            throw new GatewayException('CASAKU_LICENSE_KEY / CASAKU_QR_ID belum diisi.');
        }

        $http = Http::baseUrl(rtrim($this->baseUrl, '/'))
            ->withHeaders(['x-license-key' => $this->licenseKey])
            ->acceptJson()
            ->asJson()
            ->timeout(15)
            ->connectTimeout(5);

        try {
            /** @var Response $res */
            $res = $call($http);
        } catch (ConnectionException $e) {
            throw new GatewayException('Tidak bisa terhubung ke Casaku.', 0, $e);
        }

        if ($res->status() === 403) {
            throw new GatewayException('Casaku menolak permintaan (perangkat listener mungkin offline).');
        }
        if ($res->failed()) {
            throw new GatewayException('Casaku mengembalikan HTTP '.$res->status().'.');
        }

        return $res;
    }
}
