<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $action = mb_substr(trim((string) $request->query('aksi', '')), 0, 64);

        $logs = AuditLog::with('actor:id,username')
            ->when($action !== '', fn ($w) => $w->where('action', 'like', addcslashes($action, '%_\\').'%'))
            ->latest('id')
            ->paginate(40)
            ->withQueryString();

        return view('admin.audit.index', compact('logs', 'action'));
    }
}
