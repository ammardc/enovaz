@extends('layouts.app')
@section('title','Pesanan '.$order->number)
@section('content')
<a class="back-link" href="{{ route('orders.index') }}">@include('partials.icon',['name'=>'back']) Kembali ke Orders</a>
<div class="section-head" style="margin-top:0">
    <div><span class="eyebrow">Detail pesanan</span><h1 class="page-title">{{ $order->number }}</h1></div>
    <span class="status-pill {{ $order->status }}">{{ ['waiting_payment'=>'Menunggu pembayaran','processing'=>'Diproses','complete'=>'Selesai','expired'=>'Kedaluwarsa','review'=>'Perlu pemeriksaan','refund_processing'=>'Refund diproses','refunded'=>'Dikembalikan'][$order->status] ?? $order->status }}</span>
</div>
<div class="two-col">
    <div class="panel">
        <h2>{{ $item->product_name }}</h2>
        <div class="summary-line"><span>Paket</span><strong>{{ $item->variant_name }}</strong></div>
        <div class="summary-line"><span>Jumlah</span><strong>{{ $item->quantity }}</strong></div>
        <div class="summary-line"><span>Metode bayar</span><strong>{{ $order->payment_method === 'wallet' ? 'Saldo E-Novaz' : 'QRIS Casaku' }}</strong></div>
        <div class="summary-line total"><span>Total</span><strong>Rp{{ number_format($order->total_idr,0,',','.') }}</strong></div>
        @if(in_array($order->status,['complete','refund_processing']))
            <div class="notice" style="margin-top:18px">Akses digital sudah siap. Tampilkan hanya di perangkat pribadi dan jangan bagikan kredensialnya.</div>
            @if(isset($access))
                <div class="list-rows" style="margin-top:18px">
                    @foreach($access as $index=>$unit)
                        <div class="panel" style="background:#fbf9ff"><strong>Unit {{ $index+1 }}</strong>
                            @foreach($unit as $key=>$value)
                                <div class="summary-line"><span>{{ ucfirst(str_replace('_',' ',$key)) }}</span><strong style="word-break:break-all">{{ $value }}</strong></div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @else
                <form method="post" action="{{ route('orders.reveal',$order->number) }}" style="margin-top:18px">@csrf<button class="button">Tampilkan akses</button></form>
            @endif
        @endif
        @if($invoice && $invoice->status === 'pending')
            <a class="button" href="{{ route('payments.show',$invoice->reference) }}" style="margin-top:18px">Lihat tagihan QRIS</a>
        @endif
    </div>
    <div class="panel">
        <h2>Butuh bantuan?</h2>
        <p class="small-note">Sertakan nomor pesanan agar tim kami dapat membantu lebih cepat.</p>
        <button class="button secondary" type="button" onclick="navigator.clipboard.writeText(@js($order->number)); this.textContent='Nomor disalin'">Salin nomor pesanan</button>
        @if(config('store.telegram_url'))
            <a class="action-tile" style="margin-top:15px" href="{{ config('store.telegram_url') }}" target="_blank" rel="noopener noreferrer">Hubungi Telegram resmi @include('partials.icon',['name'=>'arrow'])</a>
        @else
            <p class="small-note">Kanal Telegram resmi akan ditambahkan pemilik toko.</p>
        @endif
        @if($order->status==='complete')
            <details style="margin-top:22px"><summary class="text-link" style="cursor:pointer">Ajukan refund</summary>
                <form method="post" action="{{ route('orders.refund',$order->number) }}" class="form-grid" style="margin-top:15px">@csrf
                    <div class="form-field"><label>Alasan</label><textarea class="form-control" name="reason" rows="3" minlength="10" required></textarea></div>
                    <button class="button secondary">Kirim pengajuan</button>
                </form>
            </details>
        @endif
    </div>
</div>
<div class="panel" style="margin-top:22px"><h2>Riwayat status</h2>
    @foreach($timeline as $event)
        <div class="summary-line"><div><strong>{{ ['waiting_payment'=>'Menunggu pembayaran','processing'=>'Diproses','complete'=>'Selesai','expired'=>'Kedaluwarsa','review'=>'Perlu pemeriksaan','refund_processing'=>'Refund diproses','refunded'=>'Dikembalikan'][$event->status] ?? $event->status }}</strong>@if($event->note)<div class="small muted">{{ $event->note }}</div>@endif</div><span class="small muted">{{ \Carbon\Carbon::parse($event->created_at)->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</span></div>
    @endforeach
</div>
@endsection
