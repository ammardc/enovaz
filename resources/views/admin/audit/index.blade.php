@extends('layouts.app')
@section('title', 'Audit log')

@section('content')
@include('admin._nav', ['heading' => 'Audit log'])

<div class="mb-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">Audit log hanya bisa ditambah. Akun database aplikasi tidak punya izin UPDATE atau DELETE, dan isi kredensial tidak pernah dicatat.</div>

<form method="GET" class="mb-4 flex gap-2">
    <input name="aksi" value="{{ $action }}" maxlength="64" placeholder="Filter aksi, misal: payment" class="input w-64">
    <button class="btn btn-ghost">Filter</button>
</form>

<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Waktu (WIB)</th><th>Aktor</th><th>Aksi</th><th>Target</th><th>Detail</th><th>IP</th></tr></thead>
        <tbody>
        @forelse($logs as $log)
            <tr>
                <td class="num whitespace-nowrap">{{ wib($log->created_at, 'd M Y H.i.s') }}</td>
                <td class="font-mono text-xs">{{ $log->actor?->username ?? 'system' }}</td>
                <td class="font-mono text-xs">{{ $log->action }}</td>
                <td class="font-mono text-xs">{{ $log->target_type ? $log->target_type.' #'.$log->target_id : '-' }}</td>
                <td class="max-w-xs truncate font-mono text-xs text-muted">{{ $log->meta ? json_encode($log->meta, JSON_UNESCAPED_UNICODE) : '' }}</td>
                <td class="font-mono text-xs text-muted">{{ $log->ip ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="py-10 text-center text-muted">Belum ada catatan.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $logs->links() }}</div>
@endsection
