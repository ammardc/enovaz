{{-- Banner beranda: kartu saldo ringkas. Tamu melihat Rp0 dan diarahkan masuk saat isi saldo. --}}
@php($u = auth()->user())
@unless($u && $u->isAdmin())
<section class="banner">
    <div class="flex items-center gap-4">
        <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-white/10">
            <svg class="size-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/></svg>
        </span>
        <div>
            <p class="text-sm text-white/60">Saldo kamu</p>
            <p class="num text-3xl font-semibold tracking-tight">{{ rupiah($u ? $u->balance : 0) }}</p>
        </div>
    </div>
    <a href="{{ $u ? route('balance.index') : route('login') }}" class="btn bg-white text-ink hover:bg-white/90">+ Isi saldo</a>
</section>
@endunless
