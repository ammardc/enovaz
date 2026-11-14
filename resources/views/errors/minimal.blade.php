@extends('layouts.app')
@section('title', $code)

@section('content')
<div class="mx-auto max-w-md py-10 text-center">
    <p class="text-6xl font-extrabold text-brand-600">{{ $code }}</p>
    <h1 class="mt-3 text-2xl font-extrabold">{{ $heading }}</h1>
    <p class="mt-2 text-muted">{{ $message }}</p>
    <a href="{{ route('home') }}" class="btn btn-primary mt-6">Kembali ke beranda</a>
</div>
@endsection
