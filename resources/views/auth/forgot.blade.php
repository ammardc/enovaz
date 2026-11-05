@extends('layouts.app')
@section('title','Lupa kata sandi')
@section('content')
<div class="min-h-screen flex items-center justify-center px-4 py-12">
    <x-card class="w-full max-w-md">
        <div class="space-y-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Atur ulang kata sandi</h1>
                <p class="text-gray-600">Masukkan email Anda. Kami akan mengirim tautan reset jika akun terdaftar.</p>
            </div>

            @if(session('status'))
                <x-alert variant="success">
                    <span>✓</span>
                    <span>{{ session('status') }}</span>
                </x-alert>
            @endif

            <form method="post" action="{{ route('password.email') }}" class="space-y-5">
                @csrf

                <x-input
                    label="Email Address"
                    type="email"
                    name="email"
                    id="email"
                    value="{{ old('email') }}"
                    error="{{ $errors->has('email') ? $errors->first('email') : null }}"
                    required
                />

                <x-button variant="primary" type="submit" class="w-full">
                    Kirim Tautan Reset
                </x-button>
            </form>

            <div class="text-center text-gray-600">
                <a href="{{ route('login') }}" class="text-[#6b2cdd] hover:text-[#5520c0] font-medium">
                    Kembali ke halaman login
                </a>
            </div>
        </div>
    </x-card>
</div>
@endsection
