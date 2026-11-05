@extends('layouts.app')
@section('title','Reset kata sandi')
@section('content')
<div class="min-h-screen flex items-center justify-center px-4 py-12">
    <x-card class="w-full max-w-md">
        <div class="space-y-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Kata sandi baru</h1>
                <p class="text-gray-600">Masukkan kata sandi baru Anda</p>
            </div>

            <form method="post" action="{{ route('password.update') }}" class="space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <x-input
                    label="Email"
                    type="email"
                    name="email"
                    id="email"
                    value="{{ $email }}"
                    error="{{ $errors->has('email') ? $errors->first('email') : null }}"
                    readonly
                />

                <x-input
                    label="Kata sandi baru"
                    type="password"
                    name="password"
                    id="password"
                    error="{{ $errors->has('password') ? $errors->first('password') : null }}"
                    required
                />

                <x-input
                    label="Ulangi kata sandi"
                    type="password"
                    name="password_confirmation"
                    id="password_confirmation"
                    error="{{ $errors->has('password_confirmation') ? $errors->first('password_confirmation') : null }}"
                    required
                />

                <x-button variant="primary" type="submit" class="w-full">
                    Simpan Kata sandi
                </x-button>
            </form>
        </div>
    </x-card>
</div>
@endsection
