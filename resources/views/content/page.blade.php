@extends('layouts.app')
@section('title',$page->title)
@section('content')<a class="back-link" href="{{ route('shop') }}">@include('partials.icon',['name'=>'back']) Kembali</a><div class="panel" style="max-width:780px"><span class="eyebrow">Informasi E-Novaz</span><h1 class="page-title">{{ $page->title }}</h1><p class="small muted">Versi {{ $page->version }}</p><div class="content-body" style="margin-top:25px">{{ $page->body }}</div></div>@endsection
