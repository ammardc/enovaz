@extends('layouts.app')
@section('title', 'Verifikasi 2 langkah')

@section('content')
<div class="mx-auto max-w-xl">
    <p class="text-xs font-medium tracking-wide text-muted uppercase">Admin</p>
    <h1 class="mt-1 text-2xl font-semibold tracking-tight">Verifikasi 2 langkah</h1>

    @if($enabled)
        <div class="panel mt-5 flex items-center gap-3">
            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-emerald-50 text-emerald-600">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-10"/></svg>
            </span>
            <div>
                <p class="font-semibold">Aktif</p>
                <p class="text-sm text-muted">Sejak {{ wib($user->totp_enabled_at) }} WIB. Kalau HP hilang, minta reset lewat server: <code class="font-mono text-xs">php artisan enovaz:2fa-reset {{ $user->username }}</code></p>
            </div>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost mt-4">Kembali ke dashboard</a>
    @else
        <p class="mt-1 text-sm text-muted">Wajib untuk admin. Setelah aktif, login admin meminta kode 6 digit dari HP.</p>

        <div class="panel mt-5 space-y-5">
            <div>
                <p class="font-semibold">1. Pasang aplikasi authenticator</p>
                <p class="text-sm text-muted">Misalnya Google Authenticator, Microsoft Authenticator, atau Aegis.</p>
            </div>

            <div>
                <p class="font-semibold">2. Scan QR ini</p>
                <div class="mt-3 flex flex-wrap items-center gap-5">
                    <div class="w-44 shrink-0 rounded-xl border border-line bg-white p-2 [&_svg]:h-auto [&_svg]:w-full">{!! $qr !!}</div>
                    <div class="min-w-0 text-sm">
                        <p class="text-muted">Tidak bisa scan? Masukkan kunci ini secara manual:</p>
                        <code class="mt-1 block font-mono text-sm break-all select-all">{{ trim(chunk_split($secret, 4, ' ')) }}</code>
                        <p class="mt-2 text-xs text-muted">Akun: {{ $user->username }} · Jenis: berbasis waktu</p>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.2fa.store') }}" data-once>
                @csrf
                <label for="code" class="font-semibold">3. Masukkan kode dari aplikasi</label>
                <div class="mt-2 flex gap-2">
                    <input id="code" name="code" class="input num text-center text-xl tracking-[0.4em]" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9 ]*" maxlength="7" required placeholder="••••••">
                    <button class="btn btn-primary shrink-0 rounded-lg font-medium">Aktifkan</button>
                </div>
                @error('code')<p class="error">{{ $message }}</p>@enderror
            </form>
        </div>
        <p class="mt-3 text-xs text-muted">Jangan bagikan QR atau kunci ini. Siapa pun yang memilikinya bisa membuat kode masuk.</p>
    @endif
</div>
@endsection
