<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\InventoryCipher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StoreFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('store.inventory_key', base64_encode(random_bytes(32)));
        config()->set('store.casaku_secret', 'test-webhook-secret');
        $this->seed();
    }

    public function test_catalog_is_public_and_product_placeholders_cannot_be_bought(): void
    {
        $this->get('/')->assertOk()->assertSee('ChatGPT Plus')->assertSee('Gemini Pro');
        $this->get('/produk/chatgpt-plus')->assertOk()->assertSee('Belum tersedia');
        $this->get('/dompet')->assertRedirect('/masuk');
    }

    public function test_product_page_shows_one_description_and_search_uses_it(): void
    {
        DB::table('products')->where('slug', 'chatgpt-plus')->update([
            'summary' => 'Teks lama yang tidak dipakai',
            'description' => "Akses ChatGPT Plus.\nDukungan sesuai paket.",
        ]);

        $this->get('/produk/chatgpt-plus')
            ->assertOk()
            ->assertSee('Akses ChatGPT Plus.')
            ->assertDontSee('Teks lama yang tidak dipakai')
            ->assertDontSee('Detail resmi paket')
            ->assertDontSee('Sebelum membeli');
        $this->get('/?q=Dukungan')->assertOk()->assertSee('ChatGPT Plus');
    }

    public function test_unverified_account_cannot_use_wallet_or_checkout(): void
    {
        $user = User::factory()->create(['email_verified_at'=>null]);
        $this->actingAs($user)->get('/dompet')->assertRedirect('/verifikasi-email');
        $this->assertSame(0,DB::table('orders')->count());
    }

    public function test_wallet_menu_opens_only_the_customers_own_history(): void
    {
        config()->set('store.telegram_url', 'https://t.me/Ammawcode');
        $user = User::factory()->create(['email_verified_at'=>now()]);
        $other = User::factory()->create(['email_verified_at'=>now()]);

        foreach ([$user, $other] as $accountUser) {
            $walletId = DB::table('wallet_accounts')->insertGetId([
                'user_id' => $accountUser->id, 'balance_idr' => 10000,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $isOwner = $accountUser->id === $user->id;
            DB::table('wallet_ledger_entries')->insert([
                'wallet_account_id' => $walletId, 'kind' => 'topup', 'amount_idr' => 10000,
                'balance_after_idr' => 10000, 'reference' => $isOwner ? 'OWN-LEDGER' : 'OTHER-LEDGER',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('payment_invoices')->insert([
                'user_id' => $accountUser->id, 'purpose' => 'wallet_topup', 'status' => 'paid',
                'reference' => $isOwner ? 'OWN-TOPUP' : 'OTHER-TOPUP',
                'base_amount_idr' => 10000, 'final_amount_idr' => 10000,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->actingAs($user)->get('/dompet')
            ->assertOk()
            ->assertSee(route('wallet.transactions'))
            ->assertSee(route('wallet.topups'))
            ->assertSee('https://t.me/Ammawcode')
            ->assertDontSee('OWN-LEDGER')
            ->assertDontSee('OWN-TOPUP');
        $this->get('/dompet/riwayat-transaksi')->assertOk()->assertSee('OWN-LEDGER')->assertDontSee('OTHER-LEDGER');
        $this->get('/dompet/riwayat-top-up')->assertOk()->assertSee('OWN-TOPUP')->assertDontSee('OTHER-TOPUP');
    }

    public function test_wallet_purchase_debits_once_and_delivers_reserved_unit(): void
    {
        [$user,$variantId] = $this->sellableFixture();
        DB::table('wallet_accounts')->where('user_id',$user->id)->update(['balance_idr'=>10000]);
        $key = (string) \Illuminate\Support\Str::uuid();
        $this->actingAs($user)->post('/checkout/'.$variantId, [
            'quantity'=>1,'expected_price'=>5000,'method'=>'wallet','checkout_key'=>$key,
        ])->assertRedirect();
        $this->actingAs($user)->post('/checkout/'.$variantId, [
            'quantity'=>1,'expected_price'=>5000,'method'=>'wallet','agree'=>'on','checkout_key'=>$key,
        ])->assertRedirect();
        $this->assertSame(5000,(int) DB::table('wallet_accounts')->where('user_id',$user->id)->value('balance_idr'));
        $this->assertSame(1,DB::table('wallet_ledger_entries')->where('kind','purchase')->count());
        $this->assertSame(1,DB::table('orders')->where('user_id',$user->id)->count());
        $this->assertSame('delivered',DB::table('inventory_units')->where('variant_id',$variantId)->value('status'));
        $this->assertSame('complete',DB::table('orders')->where('user_id',$user->id)->value('status'));
    }

    public function test_insufficient_balance_rolls_back_order_and_stock(): void
    {
        [$user,$variantId] = $this->sellableFixture();
        $this->actingAs($user)->post('/checkout/'.$variantId, [
            'quantity'=>1,'expected_price'=>5000,'method'=>'wallet','agree'=>'on','checkout_key'=>(string) \Illuminate\Support\Str::uuid(),
        ])->assertSessionHasErrors('method');
        $this->assertSame(0,DB::table('orders')->count());
        $this->assertSame('available',DB::table('inventory_units')->where('variant_id',$variantId)->value('status'));
    }

    public function test_delivered_secret_is_only_revealed_to_order_owner_on_request(): void
    {
        [$owner,$variantId] = $this->sellableFixture();
        DB::table('wallet_accounts')->where('user_id',$owner->id)->update(['balance_idr'=>10000]);
        $this->actingAs($owner)->post('/checkout/'.$variantId,[
            'quantity'=>1,'expected_price'=>5000,'method'=>'wallet','agree'=>'on',
            'checkout_key'=>(string) \Illuminate\Support\Str::uuid(),
        ])->assertRedirect();
        $number = DB::table('orders')->where('user_id',$owner->id)->value('number');
        $this->actingAs($owner)->get('/pesanan/'.$number)->assertOk()->assertDontSee('TEST-CODE');
        $other = User::factory()->create(['email_verified_at'=>now()]);
        $this->actingAs($other)->get('/pesanan/'.$number)->assertNotFound();
        $this->actingAs($other)->post('/pesanan/'.$number.'/akses')->assertNotFound();
        $this->actingAs($owner)->post('/pesanan/'.$number.'/akses')->assertOk()->assertSee('TEST-CODE');
    }

    public function test_signed_duplicate_topup_callback_credits_only_once(): void
    {
        $user = User::factory()->create(['email_verified_at'=>now()]);
        DB::table('wallet_accounts')->insert(['user_id'=>$user->id,'balance_idr'=>0,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('payment_invoices')->insert([
            'reference'=>'TU-TEST','user_id'=>$user->id,'purpose'=>'wallet_topup','status'=>'pending',
            'provider_transaction_id'=>'CSK-TEST','base_amount_idr'=>10000,'final_amount_idr'=>10027,
            'expires_at'=>now()->addMinutes(15),'created_at'=>now(),'updated_at'=>now(),
        ]);
        $raw = json_encode(['transactionId'=>'CSK-TEST','amount'=>10027,'status'=>'paid','paidAt'=>now()->toIso8601String()]);
        $headers = ['CONTENT_TYPE'=>'application/json','HTTP_X_CASAKU_SIGNATURE'=>hash_hmac('sha256',$raw,'test-webhook-secret')];
        $this->call('POST','/webhook/casaku',[],[],[],$headers,$raw)->assertOk();
        $this->call('POST','/webhook/casaku',[],[],[],$headers,$raw)->assertOk();
        $this->assertSame(10000,(int) DB::table('wallet_accounts')->where('user_id',$user->id)->value('balance_idr'));
        $this->assertSame(1,DB::table('wallet_ledger_entries')->where('kind','topup')->count());
    }

    public function test_qris_order_uses_provider_final_amount_and_delivers_once(): void
    {
        [$user,$variantId] = $this->sellableFixture();
        config()->set('store.payments_enabled',true);
        config()->set('store.casaku_live_verified',true);
        config()->set('store.casaku_key','test-license');
        config()->set('store.casaku_qr_id','test-qr-id');
        Http::fake(['api.casaku.id/api/generate/v2/qris'=>Http::response(['data'=>[
            'transactionId'=>'CSK-ORDER-1','totalAmount'=>5027,'qr_string'=>'000201TEST',
            'expiredAt'=>now()->addMinutes(15)->toIso8601String(),
        ]],200)]);
        $this->actingAs($user)->post('/checkout/'.$variantId,[
            'quantity'=>1,'expected_price'=>5000,'method'=>'qris','agree'=>'on',
            'checkout_key'=>(string) \Illuminate\Support\Str::uuid(),
        ])->assertRedirect();
        $invoice = DB::table('payment_invoices')->where('purpose','order')->first();
        $this->assertSame(5000,(int) $invoice->base_amount_idr);
        $this->assertSame(5027,(int) $invoice->final_amount_idr);
        $this->assertSame('reserved',DB::table('inventory_units')->where('variant_id',$variantId)->value('status'));
        $raw = json_encode(['transactionId'=>'CSK-ORDER-1','amount'=>5027,'status'=>'paid','paidAt'=>now()->toIso8601String()]);
        $headers = ['CONTENT_TYPE'=>'application/json','HTTP_X_CASAKU_SIGNATURE'=>hash_hmac('sha256',$raw,'test-webhook-secret')];
        $this->call('POST','/webhook/casaku',[],[],[],$headers,$raw)->assertOk();
        $this->call('POST','/webhook/casaku',[],[],[],$headers,$raw)->assertOk();
        $this->assertSame('complete',DB::table('orders')->where('user_id',$user->id)->value('status'));
        $this->assertSame('delivered',DB::table('inventory_units')->where('variant_id',$variantId)->value('status'));
        $this->assertSame(0,DB::table('wallet_ledger_entries')->where('kind','topup')->count());
    }

    public function test_invalid_signature_and_amount_mismatch_do_not_credit(): void
    {
        $user = User::factory()->create(['email_verified_at'=>now()]);
        DB::table('wallet_accounts')->insert(['user_id'=>$user->id,'balance_idr'=>0,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('payment_invoices')->insert([
            'reference'=>'TU-MISMATCH','user_id'=>$user->id,'purpose'=>'wallet_topup','status'=>'pending',
            'provider_transaction_id'=>'CSK-MISMATCH','base_amount_idr'=>10000,'final_amount_idr'=>10027,
            'expires_at'=>now()->addMinutes(15),'created_at'=>now(),'updated_at'=>now(),
        ]);
        $raw = json_encode(['transactionId'=>'CSK-MISMATCH','amount'=>9000,'status'=>'paid','paidAt'=>now()->toIso8601String()]);
        $this->call('POST','/webhook/casaku',[],[],[],['CONTENT_TYPE'=>'application/json','HTTP_X_CASAKU_SIGNATURE'=>str_repeat('0',64)],$raw)->assertStatus(401);
        $headers = ['CONTENT_TYPE'=>'application/json','HTTP_X_CASAKU_SIGNATURE'=>hash_hmac('sha256',$raw,'test-webhook-secret')];
        $this->call('POST','/webhook/casaku',[],[],[],$headers,$raw)->assertOk();
        $this->assertSame(0,(int) DB::table('wallet_accounts')->where('user_id',$user->id)->value('balance_idr'));
        $this->assertSame('review',DB::table('payment_invoices')->where('reference','TU-MISMATCH')->value('status'));
    }

    public function test_approved_wallet_refund_is_credited_once_and_audited(): void
    {
        [$user,$variantId] = $this->sellableFixture();
        DB::table('wallet_accounts')->where('user_id',$user->id)->update(['balance_idr'=>10000]);
        $this->actingAs($user)->post('/checkout/'.$variantId,[
            'quantity'=>1,'expected_price'=>5000,'method'=>'wallet','agree'=>'on',
            'checkout_key'=>(string) \Illuminate\Support\Str::uuid(),
        ])->assertRedirect();
        $number = DB::table('orders')->where('user_id',$user->id)->value('number');
        $this->actingAs($user)->post('/pesanan/'.$number.'/refund',['reason'=>'Akses tidak sesuai dengan keterangan paket.'])->assertRedirect();
        $refund = DB::table('refunds')->first();
        $admin = User::factory()->create(['role'=>'owner','email_verified_at'=>now()]);
        $this->actingAs($admin)->withSession(['admin_2fa_user'=>$admin->id,'admin_2fa_at'=>now()->timestamp])
            ->post('/admin/refund/'.$refund->id,['action'=>'wallet','reason'=>'Pengajuan pelanggan disetujui.'])->assertRedirect();
        $this->assertSame(10000,(int) DB::table('wallet_accounts')->where('user_id',$user->id)->value('balance_idr'));
        $this->assertSame(1,DB::table('wallet_ledger_entries')->where('kind','refund')->count());
        $this->assertSame('completed',DB::table('refunds')->where('id',$refund->id)->value('status'));
        $this->assertSame(1,DB::table('admin_audit_logs')->where('action','refund_completed')->count());
    }

    public function test_wallet_adjustment_requires_a_second_admin(): void
    {
        $customer = User::factory()->create(['email_verified_at'=>now()]);
        DB::table('wallet_accounts')->insert(['user_id'=>$customer->id,'balance_idr'=>0,'created_at'=>now(),'updated_at'=>now()]);
        $first = User::factory()->create(['role'=>'admin','email_verified_at'=>now()]);
        $second = User::factory()->create(['role'=>'owner','email_verified_at'=>now()]);
        $this->actingAs($first)->withSession(['admin_2fa_user'=>$first->id,'admin_2fa_at'=>now()->timestamp])
            ->post('/admin/koreksi-saldo',['email'=>$customer->email,'amount_idr'=>2500,'reason'=>'Koreksi pembayaran pelanggan yang sah.'])->assertRedirect();
        $id = DB::table('admin_adjustments')->value('id');
        $this->actingAs($first)->withSession(['admin_2fa_user'=>$first->id,'admin_2fa_at'=>now()->timestamp])
            ->post('/admin/koreksi-saldo/'.$id,['decision'=>'approve','reason'=>'Setuju koreksi.'])->assertSessionHasErrors('decision');
        $this->assertSame(0,(int) DB::table('wallet_accounts')->where('user_id',$customer->id)->value('balance_idr'));
        $this->actingAs($second)->withSession(['admin_2fa_user'=>$second->id,'admin_2fa_at'=>now()->timestamp])
            ->post('/admin/koreksi-saldo/'.$id,['decision'=>'approve','reason'=>'Bukti telah diperiksa.'])->assertRedirect();
        $this->assertSame(2500,(int) DB::table('wallet_accounts')->where('user_id',$customer->id)->value('balance_idr'));
        $this->assertSame(1,DB::table('wallet_ledger_entries')->where('kind','adjustment')->count());
    }

    public function test_verified_owner_with_2fa_can_open_admin_modules(): void
    {
        $owner = User::factory()->create(['role'=>'owner','email_verified_at'=>now()]);
        $product = DB::table('products')->where('slug','chatgpt-plus')->first();
        $variant = DB::table('product_variants')->where('product_id',$product->id)->first();
        $this->actingAs($owner)->withSession(['admin_2fa_user'=>$owner->id,'admin_2fa_at'=>now()->timestamp]);
        foreach (['/admin','/admin/produk','/admin/produk/'.$product->id,'/admin/produk/'.$product->id.'/varian',
            '/admin/produk/'.$product->id.'/media','/admin/stok','/admin/stok/'.$variant->id,'/admin/kategori','/admin/konten',
            '/admin/pesanan','/admin/pembayaran','/admin/refund','/admin/pelanggan','/admin/koreksi-saldo','/admin/laporan','/admin/audit'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_owner_can_publish_product_without_access_or_refund_terms(): void
    {
        [, $variantId] = $this->sellableFixture();
        $product = DB::table('products')->where('slug', 'chatgpt-plus')->first();
        DB::table('products')->where('id', $product->id)->update(['status' => 'coming_soon']);
        DB::table('product_variants')->where('id', $variantId)->update(['active' => false]);

        $owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $this->actingAs($owner)->withSession(['admin_2fa_user' => $owner->id, 'admin_2fa_at' => now()->timestamp]);
        $this->get('/admin/produk/'.$product->id)->assertOk()
            ->assertDontSee('Ketentuan akses')->assertDontSee('Ketentuan refund');

        $this->post('/admin/produk/'.$product->id, [
            'name' => $product->name, 'category_id' => $product->category_id, 'status' => 'published',
            'description' => 'Akses ChatGPT Plus sesuai paket.', 'usage_instructions' => 'Gunakan kode yang dikirim.',
            'variant_name' => 'Paket utama', 'price_idr' => 5000, 'limit_per_customer' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $saved = DB::table('products')->where('id', $product->id)->first();
        $this->assertSame('published', $saved->status);
        $this->assertNull($saved->access_terms);
        $this->assertNull($saved->refund_terms);
    }

    public function test_owner_can_find_and_add_one_encrypted_stock_unit(): void
    {
        $owner = User::factory()->create(['role'=>'owner','email_verified_at'=>now()]);
        $product = DB::table('products')->where('slug','chatgpt-plus')->first();
        $variant = DB::table('product_variants')->where('product_id',$product->id)->first();
        $this->actingAs($owner)->withSession(['admin_2fa_user'=>$owner->id,'admin_2fa_at'=>now()->timestamp]);
        $this->get('/admin/stok')->assertOk()->assertSee('Tambah stok');
        $this->get('/admin/stok/'.$variant->id)->assertOk()->assertSee('Tambah satu stok');
        $this->post('/admin/stok/'.$variant->id.'/unit',[
            'code'=>'SINGLE-TEST-CODE','instructions'=>'Instruksi unit uji.',
        ])->assertRedirect();
        $stock = DB::table('inventory_units')->where('variant_id',$variant->id)->first();
        $this->assertNotNull($stock);
        $this->assertStringNotContainsString('SINGLE-TEST-CODE',$stock->ciphertext);
        $this->assertSame('SINGLE-TEST-CODE',app(InventoryCipher::class)->decrypt($stock->ciphertext)['code']);
        $this->post('/admin/stok/'.$variant->id.'/unit',['code'=>'SINGLE-TEST-CODE'])->assertSessionHasErrors('code');
        $this->assertSame(1,DB::table('inventory_units')->where('variant_id',$variant->id)->count());
    }

    public function test_report_csv_is_authorized_and_escapes_spreadsheet_formulas(): void
    {
        [$customer,$variantId] = $this->sellableFixture();
        DB::table('wallet_accounts')->where('user_id',$customer->id)->update(['balance_idr'=>10000]);
        $this->actingAs($customer)->post('/checkout/'.$variantId,[
            'quantity'=>1,'expected_price'=>5000,'method'=>'wallet','agree'=>'on',
            'checkout_key'=>(string) \Illuminate\Support\Str::uuid(),
        ])->assertRedirect();
        DB::table('order_items')->update(['product_name'=>'=SUM(1,1)']);
        $this->actingAs($customer)->get('/admin/laporan/ekspor')->assertForbidden();

        $owner = User::factory()->create(['role'=>'owner','email_verified_at'=>now()]);
        $response = $this->actingAs($owner)
            ->withSession(['admin_2fa_user'=>$owner->id,'admin_2fa_at'=>now()->timestamp])
            ->get('/admin/laporan/ekspor');
        $response->assertOk()->assertDownload();
        $this->assertStringContainsString("'=SUM(1,1)",$response->streamedContent());
        $this->assertSame(1,DB::table('admin_audit_logs')->where('action','report_exported')->count());
    }

    private function sellableFixture(): array
    {
        $user = User::factory()->create(['email_verified_at'=>now()]);
        DB::table('wallet_accounts')->insert(['user_id'=>$user->id,'balance_idr'=>0,'created_at'=>now(),'updated_at'=>now()]);
        $product = DB::table('products')->where('slug','chatgpt-plus')->first();
        DB::table('products')->where('id',$product->id)->update(['status'=>'published']);
        $variant = DB::table('product_variants')->where('product_id',$product->id)->first();
        DB::table('product_variants')->where('id',$variant->id)->update(['active'=>true,'price_idr'=>5000]);
        $unit = ['code'=>'TEST-CODE','account'=>'','password'=>'','instructions'=>'Uji'];
        $cipher = app(InventoryCipher::class);
        DB::table('inventory_units')->insert([
            'variant_id'=>$variant->id,'fingerprint'=>$cipher->fingerprint($unit),'ciphertext'=>$cipher->encrypt($unit),
            'status'=>'available','created_at'=>now(),'updated_at'=>now(),
        ]);
        return [$user,$variant->id];
    }
}
