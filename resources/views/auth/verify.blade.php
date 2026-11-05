@extends('layouts.app')
@section('title','Verifikasi email')
@section('content')<div class="auth-wrap"><div class="panel"><span class="eyebrow">Satu langkah lagi</span><h1>Verifikasi email</h1><p>Kami mengirim tautan verifikasi ke {{ auth()->user()->email }}. Buka tautannya sebelum bertransaksi.</p><form method="post" action="{{ route('verification.send') }}">@csrf<button class="button full">Kirim ulang tautan</button></form></div></div>@endsection
