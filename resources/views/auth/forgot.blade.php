@extends('layouts.app')
@section('title','Lupa kata sandi')
@section('content')<div class="auth-wrap"><div class="panel"><h1>Atur ulang kata sandi</h1><p>Masukkan email. Kami akan mengirim tautan reset jika akun terdaftar.</p><form method="post" action="{{ route('password.email') }}" class="form-grid">@csrf<div class="form-field"><label for="email">Email</label><input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" required></div><button class="button full">Kirim tautan</button></form></div></div>@endsection
