@extends('layouts.app')

@section('content')
@include('partials.banner')

@if($categories->isNotEmpty())
<section class="panel mt-6">
    <div class="flex items-center gap-2.5 border-b border-line pb-4">
        <h2 class="text-lg font-semibold tracking-tight">Kategori</h2>
        <span class="rounded-full bg-canvas px-2.5 py-0.5 text-xs font-semibold text-muted">{{ $categories->count() }}</span>
    </div>
    <div class="mt-5 grid grid-cols-[repeat(auto-fill,minmax(96px,1fr))] gap-3">
        <a href="{{ route('home', array_filter(['q' => $q, 'urut' => $sort === 'populer' ? null : $sort])) }}#produk" class="cat-tile {{ $category === '' ? 'is-active' : '' }}">
            <span class="cat-icon">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="6.5" height="6.5" rx="1.5"/><rect x="13.5" y="4" width="6.5" height="6.5" rx="1.5"/><rect x="4" y="13.5" width="6.5" height="6.5" rx="1.5"/><rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1.5"/></svg>
            </span>
            Semua
        </a>
        @foreach($categories as $cat)
            <a href="{{ route('home', array_filter(['kategori' => $cat->name, 'q' => $q, 'urut' => $sort === 'populer' ? null : $sort])) }}#produk" class="cat-tile {{ $category === $cat->name ? 'is-active' : '' }}">
                <span class="cat-icon">
                    @if($cat->mark)
                        <img src="{{ $cat->mark }}" alt="" class="size-9">
                    @else
                        <span class="grid size-9 place-items-center rounded-lg text-xs font-bold text-white {{ category_class($cat->name) }}">{{ mb_strtoupper(mb_substr($cat->name, 0, 2)) }}</span>
                    @endif
                </span>
                {{ $cat->name }}
            </a>
        @endforeach
    </div>
</section>
@endif

<section id="produk" class="mt-10 scroll-mt-24">
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-3xl font-semibold tracking-tight">
                @if($q !== '') Hasil “{{ $q }}” @elseif($category !== '') {{ $category }} @else Produk @endif
            </h2>
            <p class="mt-1 text-sm text-muted">{{ $products->total() }} produk</p>
        </div>
        <form method="GET" action="{{ route('home') }}#produk" class="flex items-center gap-2">
            @if($q !== '')<input type="hidden" name="q" value="{{ $q }}">@endif
            @if($category !== '')<input type="hidden" name="kategori" value="{{ $category }}">@endif
            <label for="urut" class="text-sm text-muted">Urutkan</label>
            <select id="urut" name="urut" class="input w-auto py-2" data-autosubmit>
                @foreach(['populer' => 'Terpopuler', 'terbaru' => 'Terbaru', 'termurah' => 'Termurah', 'termahal' => 'Termahal'] as $k => $v)
                    <option value="{{ $k }}" @selected($sort === $k)>{{ $v }}</option>
                @endforeach
            </select>
            <noscript><button class="btn btn-ghost">Terapkan</button></noscript>
        </form>
    </div>

    @if($products->isEmpty())
        <div class="panel py-12 text-center text-muted">Produk tidak ditemukan. <a href="{{ route('home') }}" class="font-semibold text-brand-600">Reset</a></div>
    @else
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-[repeat(auto-fill,minmax(200px,1fr))] sm:gap-4">
            @foreach($products as $product)
                @include('partials.product-card', ['product' => $product])
            @endforeach
        </div>
        <div class="mt-8">{{ $products->links() }}</div>
    @endif
</section>

@if($lastPurchase)
    @php($lp = $lastPurchase->orderItem)
    <div class="toast-buy" role="status">
        <div class="shrink-0 overflow-hidden rounded-xl ring-1 ring-black/5 size-12">@include('partials.thumb', ['product' => $lp->product, 'class' => 'size-12', 'compact' => true])</div>
        <div class="min-w-0 text-sm">
            <span class="tag border border-orange-200 bg-orange-50 text-orange-600">Pembelian baru</span>
            <p class="mt-1 truncate"><b class="text-[#f0532c]">{{ mask_username($lp->order->user->username) }}</b> membeli</p>
            <p class="truncate text-muted">{{ $lp->product->name }} · {{ $lastPurchase->delivered_at->diffForHumans() }}</p>
        </div>
        <button type="button" class="ml-auto self-start text-muted hover:text-ink" data-dismiss aria-label="Tutup">✕</button>
    </div>
@endif
@endsection
