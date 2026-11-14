{{-- $product, $class (opsional), $text (ukuran teks), $compact (true = logo saja, tanpa nama merek) --}}
@php($mark = $product->brandMark())
@if($mark)
    <div class="relative grid place-items-center overflow-hidden bg-[#f6f5fa] {{ $class ?? 'aspect-[4/3]' }}">
        @if($compact ?? false)
            <img src="{{ $mark }}" alt="" class="size-[78%]">
        @else
            {{-- Latar: semburat warna merek + logo besar samar + cap E-Novaz --}}
            <div class="thumb-art {{ $product->brandClass() }}" aria-hidden="true">
                <img src="{{ $mark }}" alt="" class="thumb-watermark">
            </div>
            <div class="relative flex max-w-full items-center gap-2 px-3 sm:gap-2.5">
                <img src="{{ $mark }}" alt="" class="size-8 shrink-0 sm:size-10">
                <span class="truncate text-base font-semibold tracking-tight text-ink sm:text-xl">{{ $product->brandName() }}</span>
            </div>
            <span class="thumb-stamp">
                <img src="{{ asset('images/logo-icon.png') }}" alt="" class="size-3.5 rounded-[4px]">E-Novaz
            </span>
        @endif
    </div>
@else
    <div class="relative grid place-items-center overflow-hidden text-white {{ $product->accentClass() }} {{ $class ?? 'aspect-[4/3]' }}">
        @if($product->imageUrl())
            <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="absolute inset-0 size-full object-cover" loading="lazy">
        @else
            <span class="{{ $text ?? 'text-3xl' }} font-extrabold tracking-tight drop-shadow">{{ $product->initials() }}</span>
        @endif
    </div>
@endif
