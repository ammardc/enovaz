@extends('layouts.app')
@section('title', 'Pengguna')

@section('content')
@include('admin._nav', ['heading' => 'Pengguna'])

<form method="GET" class="mb-4 flex gap-2">
    <input name="q" value="{{ $q }}" maxlength="32" placeholder="Cari username" class="input w-56">
    <button class="btn btn-ghost">Cari</button>
</form>

<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Username</th><th>Nama</th><th class="text-right!">Pesanan</th><th class="text-right!">Saldo</th><th>Terdaftar</th><th>Role</th><th>Tambah saldo</th></tr></thead>
        <tbody>
        @foreach($users as $u)
            <tr>
                <td class="font-semibold">{{ $u->username }}</td>
                <td>{{ $u->name }}</td>
                <td class="num text-right">{{ $u->orders_count }}</td>
                <td class="num text-right">{{ rupiah($u->balance) }}</td>
                <td class="num">{{ wib($u->created_at, 'd M Y') }}</td>
                <td>
                    @if($u->is(auth()->user()))
                        <span class="pill pill-paid">{{ $u->role }} (kamu)</span>
                    @else
                        <form method="POST" action="{{ route('admin.users.role', $u) }}" class="flex items-center gap-2" data-confirm="Ubah role {{ $u->username }}? Tindakan ini dicatat di audit log.">
                            @csrf @method('PUT')
                            <select name="role" class="input w-auto py-1.5">
                                <option value="pembeli" @selected($u->role === 'pembeli')>pembeli</option>
                                <option value="admin" @selected($u->role === 'admin')>admin</option>
                            </select>
                            <button class="btn btn-ghost px-3 py-1.5">Simpan</button>
                        </form>
                    @endif
                </td>
                <td>
                    @if($u->role === 'pembeli')
                        <form method="POST" action="{{ route('admin.users.credit', $u) }}" class="flex items-center gap-1.5" data-confirm="Tambah saldo {{ $u->username }}? Tindakan ini dicatat di audit log.">
                            @csrf
                            <input name="amount" type="number" inputmode="numeric" min="1000" max="{{ config('enovaz.admin_credit_max') }}" step="1000" required placeholder="Nominal" class="input num w-28 py-1.5" aria-label="Nominal saldo untuk {{ $u->username }}">
                            <input name="note" maxlength="120" required placeholder="Catatan" class="input w-32 py-1.5" aria-label="Catatan">
                            <button class="btn btn-primary px-3 py-1.5">+</button>
                        </form>
                    @else
                        <span class="text-xs text-muted">-</span>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<p class="mt-2 text-xs text-muted">Mengubah role dan menambah saldo memerlukan konfirmasi password, dan dicatat di audit log.</p>
@if($errors->has('amount') || $errors->has('note'))<p class="error">{{ $errors->first('amount') ?: $errors->first('note') }}</p>@endif
<div class="mt-4">{{ $users->links() }}</div>
@endsection
