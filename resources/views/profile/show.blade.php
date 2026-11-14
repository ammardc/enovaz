@extends('layouts.app')
@section('title', 'Profil')

@section('content')
<div class="mx-auto max-w-3xl space-y-5">
    {{-- Ringkasan akun --}}
    <div class="panel flex flex-wrap items-center gap-4">
        <span class="grid size-14 shrink-0 place-items-center rounded-2xl bg-brand-100 text-xl font-semibold text-brand-700">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
        <div class="min-w-0 flex-1">
            <h1 class="truncate text-xl font-semibold tracking-tight">{{ $user->name }}</h1>
            <p class="text-sm text-muted">{{ '@'.$user->username }} · bergabung {{ wib($user->created_at, 'd M Y') }}</p>
        </div>
        @if($user->isAdmin())
            <span class="chip">Admin</span>
        @endif
    </div>

    @unless($user->isAdmin())
        <div class="grid grid-cols-2 gap-3">
            <a href="{{ route('balance.index') }}" class="panel flex items-center justify-between gap-3 p-4 hover:border-ink/15">
                <div class="min-w-0">
                    <p class="text-xs text-muted">Saldo</p>
                    <p class="num truncate text-lg font-semibold tracking-tight">{{ rupiah($user->balance) }}</p>
                </div>
                <svg class="size-5 shrink-0 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </a>
            <a href="{{ route('orders.index') }}" class="panel flex items-center justify-between gap-3 p-4 hover:border-ink/15">
                <div class="min-w-0">
                    <p class="text-xs text-muted">Pesanan</p>
                    <p class="num text-lg font-semibold tracking-tight">{{ $user->orders_count }}</p>
                </div>
                <svg class="size-5 shrink-0 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </a>
        </div>
    @endunless

    {{-- Ubah nama --}}
    <form method="POST" action="{{ route('profile.name') }}" class="panel" data-once>
        @csrf @method('PUT')
        <h2 class="font-semibold">Nama</h2>
        <div class="mt-3 flex flex-wrap gap-2">
            <input name="name" value="{{ old('name', $user->name) }}" maxlength="100" required class="input min-w-0 flex-1" autocomplete="name" aria-label="Nama">
            <button class="btn btn-primary font-medium">Simpan</button>
        </div>
        @error('name')<p class="error">{{ $message }}</p>@enderror
        <p class="help">Username tidak bisa diubah.</p>
    </form>

    {{-- Ganti password --}}
    <form method="POST" action="{{ route('profile.password') }}" class="panel space-y-3" data-once>
        @csrf @method('PUT')
        <h2 class="font-semibold">Ganti password</h2>
        <div>
            <label for="current_password" class="label">Password lama</label>
            <input id="current_password" name="current_password" type="password" class="input" autocomplete="current-password" maxlength="128" required>
            @error('current_password')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label for="password" class="label">Password baru</label>
                <input id="password" name="password" type="password" class="input" autocomplete="new-password" minlength="{{ config('enovaz.password_min.'.($user->isAdmin() ? 'admin' : 'pembeli')) }}" maxlength="128" required>
                @error('password')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="label">Ulangi password baru</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password" maxlength="128" required>
            </div>
        </div>
        <p class="help">Min. {{ config('enovaz.password_min.'.($user->isAdmin() ? 'admin' : 'pembeli')) }} karakter, huruf + angka. Perangkat lain akan otomatis keluar.</p>
        <button class="btn btn-primary font-medium" data-loading="Menyimpan…">Ganti password</button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn btn-ghost w-full font-medium text-red-600">Keluar</button>
    </form>
</div>
@endsection
