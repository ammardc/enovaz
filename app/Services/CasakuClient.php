<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class CasakuClient
{
    public function ready(): bool
    {
        return (bool) (config('store.payments_enabled') && config('store.casaku_live_verified') &&
            config('store.casaku_key') && config('store.casaku_secret') && config('store.casaku_qr_id'));
    }

    public function configured(): bool
    {
        return (bool) (config('store.casaku_key') && config('store.casaku_secret') && config('store.casaku_qr_id'));
    }

    private function post(string $path, array $payload): array
    {
        if (! $this->configured()) throw new RuntimeException('Casaku belum dikonfigurasi.');
        $response = Http::withHeaders(['x-license-key' => config('store.casaku_key')])
            ->acceptJson()->timeout(12)
            ->post(rtrim(config('store.casaku_base_url'), '/').$path, $payload);
        if (! $response->successful()) throw new RuntimeException('Casaku menolak permintaan dengan status '.$response->status().'.');
        return $response->json() ?: [];
    }

    public function generate(int $amount): array
    {
        if (! $this->ready()) throw new RuntimeException('Pembayaran QRIS belum diaktifkan.');
        $data = data_get($this->post('/api/generate/v2/qris', [
            'qr_id' => config('store.casaku_qr_id'), 'amount' => $amount,
            'useUniqueCode' => true, 'packageIds' => array_values(config('store.casaku_package_ids')),
            'expiredInMinutes' => 15, 'qrType' => 'dynamic', 'paymentMethod' => 'qris',
            'useQris' => true, 'prefix' => 'ENV',
        ]), 'data');
        $id = data_get($data, 'transactionId');
        $final = data_get($data, 'totalAmount');
        $qr = data_get($data, 'qr_string');
        if (! is_string($id) || ! is_numeric($final) || ! is_string($qr) || $qr === '' || (int) $final < $amount) {
            throw new RuntimeException('Respons Casaku tidak lengkap; invoice memerlukan pemeriksaan.');
        }
        return ['id' => $id, 'amount' => (int) $final, 'qr' => $qr,
            'expires_at' => data_get($data, 'expiredAt') ?: now()->addMinutes(15)->toIso8601String()];
    }

    public function status(string $id): array
    {
        $data = data_get($this->post('/api/generate/check-status', ['transactionId' => $id]), 'data');
        if (! is_array($data) || data_get($data, 'transactionId') !== $id) throw new RuntimeException('Respons status Casaku tidak cocok.');
        return $data;
    }

    public function listenerStatus(): array
    {
        if (! $this->configured()) throw new RuntimeException('Casaku belum dikonfigurasi.');
        $response = Http::withHeaders(['x-license-key' => config('store.casaku_key')])
            ->acceptJson()->timeout(8)->get(rtrim(config('store.casaku_base_url'), '/').'/api/device/check');
        if (! $response->successful()) throw new RuntimeException('Status listener tidak tersedia.');
        return $response->json() ?: [];
    }
}
