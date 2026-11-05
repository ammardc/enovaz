<?php

namespace App\Jobs;

use App\Services\PaymentSettlement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCasakuPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [5,30,120,300];

    public function __construct(public string $transactionId, public int $amount, public ?string $paidAt, public string $eventHash) {}

    public function handle(PaymentSettlement $settlement): void
    {
        $settlement->paid($this->transactionId,$this->amount,$this->paidAt,$this->eventHash);
    }
}
