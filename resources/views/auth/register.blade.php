@extends('layouts.app')
@section('title','Daftar')
@section('content')
<div class="min-h-screen flex items-center justify-center px-4 py-12">
    <x-card class="w-full max-w-md">
        <div class="space-y-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Daftar</h1>
                <p class="text-gray-600">Buat akun baru untuk memulai</p>
            </div>

            <form method="post" action="{{ route('register') }}" class="space-y-5">
                @csrf

                <x-input
                    label="Nama Lengkap"
                    type="text"
                    name="name"
                    id="name"
                    value="{{ old('name') }}"
                    autocomplete="name"
                    error="{{ $errors->has('name') ? $errors->first('name') : null }}"
                    required
                />

                <div>
                    <x-input
                        label="Username"
                        type="text"
                        name="username"
                        id="username"
                        value="{{ old('username') }}"
                        autocomplete="username"
                        error="{{ $errors->has('username') ? $errors->first('username') : null }}"
                        required
                    />
                    <p class="mt-2 text-sm text-gray-500">Hanya huruf, angka, underscore dan dash</p>
                </div>

                <x-input
                    label="Email"
                    type="email"
                    name="email"
                    id="email"
                    value="{{ old('email') }}"
                    autocomplete="email"
                    error="{{ $errors->has('email') ? $errors->first('email') : null }}"
                    required
                />

                <div>
                    <x-input
                        label="Kata sandi"
                        type="password"
                        name="password"
                        id="password"
                        autocomplete="new-password"
                        error="{{ $errors->has('password') ? $errors->first('password') : null }}"
                        required
                    />
                    <p class="mt-2 text-sm text-gray-500">Minimal 10 karakter, huruf dan angka</p>
                </div>

                <x-input
                    label="Ulangi Kata sandi"
                    type="password"
                    name="password_confirmation"
                    id="password_confirmation"
                    autocomplete="new-password"
                    error="{{ $errors->has('password_confirmation') ? $errors->first('password_confirmation') : null }}"
                    required
                />

                <x-button variant="primary" type="submit" class="w-full">
                    Daftar Sekarang
                </x-button>
            </form>

            <div class="text-center text-gray-600">
                Sudah punya akun?
                <a href="{{ route('login') }}" class="text-[#6b2cdd] hover:text-[#5520c0] font-medium">
                    Masuk di sini
                </a>
            </div>
        </div>
    </x-card>
</div>
@endsection
