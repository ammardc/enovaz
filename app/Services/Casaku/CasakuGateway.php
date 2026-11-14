<?php

namespace App\Services\Casaku;

use Carbon\CarbonInterface;

interface CasakuGateway
{
    /**
     * Membuat tagihan QRIS dinamis dengan kode unik.
     *
     * @return array{transaction_id:string,total_amount:int,qr_string:string}
     */
    public function createQris(int $baseAmount, int $expiresMinutes): array;

    /**
     * @return array{transaction_id:?string,status:string,paid_at:?CarbonInterface}
     *                                                                          status: pending|paid|cancel|expired
     */
    public function checkStatus(string $transactionId): array;

    public function cancel(string $transactionId): void;
}
