@php($stock = $product->stock())
<article class="group flex flex-col rounded-[10px] border border-line p-2.5 shadow-sm shadow-ink/[0.03] transition {{ $stock === 0 ? 'bg-slate-50 opacity-60 grayscale' : 'bg-white hover:border-ink/15 hover:shadow-lg hover:shadow-ink/5' }}">
    <a href="{{ route('products.show', $product->slug) }}" class="relative block overflow-hidden rounded-md">
        @include('partials.thumb', ['product' => $product])

        @if($stock === 0)
            <span class="chip absolute top-2 left-2">Habis</span>
        @elseif($stock <= config('enovaz.low_stock'))
            <span class="chip absolute top-2 left-2"><span class="size-1.5 rounded-full bg-amber-500"></span>Sisa {{ $stock }}</span>
        @elseif($product->isNew())
            <span class="chip absolute top-2 left-2"><span class="size-1.5 rounded-full bg-brand-500"></span>Baru</span>
        @endif
    </a>

    <div class="flex flex-1 flex-col px-1.5 pt-3 pb-1">
        <h3 class="text-[15px] leading-snug font-medium tracking-tight"><a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a></h3>

        <div class="mt-auto flex items-baseline justify-between gap-2 pt-3">
            <p class="num text-lg font-semibold tracking-tight">{{ rupiah($product->price) }}</p>
            <span class="num shrink-0 text-xs {{ $stock > 0 && $stock <= config('enovaz.low_stock') ? 'font-medium text-amber-600' : 'text-muted' }}">Stok {{ $stock }}</span>
        </div>
        <p class="mt-1 flex items-center gap-1.5 text-xs text-emerald-700">
            <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
            Garansi penuh
        </p>
        <p class="num mt-0.5 text-xs text-muted">{{ number_format($product->sold(), 0, ',', '.') }} terjual · {{ $product->duration }}</p>

        <div class="mt-3">
            @if($stock === 0)
                <button class="btn btn-ghost w-full rounded-lg font-medium" disabled>Stok habis</button>
            @else
                <a href="{{ route('products.show', $product->slug) }}" class="btn btn-primary w-full rounded-lg font-medium">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1.3"/><circle cx="18" cy="20" r="1.3"/><path d="M2 3h3l2.6 12.2a2 2 0 0 0 2 1.6h8.2a2 2 0 0 0 2-1.5L21.5 8H6.2"/></svg>
                    Beli Sekarang
                </a>
            @endif
        </div>
    </div>
</article>
