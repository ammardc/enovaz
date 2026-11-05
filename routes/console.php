<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use App\Services\CasakuClient;
use App\Services\PaymentSettlement;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('store:owner {email}', function (string $email) {
    $user = DB::table('users')->where('email', $email)->first();
    if (! $user || ! $user->email_verified_at) {
        $this->error('Akun harus ada dan emailnya sudah terverifikasi.');
        return 1;
    }
    DB::table('users')->where('id', $user->id)->update(['role' => 'owner', 'updated_at' => now()]);
    DB::table('admin_audit_logs')->insert(['admin_id' => $user->id, 'action' => 'owner_promoted_cli',
        'subject_type' => 'user', 'subject_id' => $user->id, 'context' => '{}', 'created_at' => now()]);
    $this->info('Owner aktif: '.$email);
})->purpose('Promote a verified user to initial owner from server CLI');

Artisan::command('store:reconcile', function (CasakuClient $casaku, PaymentSettlement $settlement) {
    if (! $casaku->configured()) { $this->info('Casaku belum dikonfigurasi.'); return; }
    $invoices = DB::table('payment_invoices')->where('status', 'pending')->whereNotNull('provider_transaction_id')
        ->where('created_at', '<', now()->subSeconds(30))->orderBy('id')->limit(30)->get();
    foreach ($invoices as $invoice) {
        try {
            $status = $casaku->status($invoice->provider_transaction_id);
            if (data_get($status, 'status') === 'paid') {
                $settlement->paid($invoice->provider_transaction_id, (int) data_get($status, 'amount'),
                    data_get($status, 'paidAt'), 'reconcile:'.hash('sha256', json_encode($status)));
            } elseif (in_array(data_get($status, 'status'), ['expired','cancel'], true)) {
                $settlement->expired($invoice->provider_transaction_id);
            }
        } catch (\Throwable $e) { report($e); }
    }
    $this->info('Diperiksa: '.$invoices->count().' invoice.');
})->purpose('Reconcile pending Casaku invoices with provider status');

Schedule::command('store:reconcile')->everyMinute()->withoutOverlapping();
