<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BalanceEntry;
use App\Models\User;
use App\Services\Audit;
use App\Services\BalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 32);

        $users = User::withCount('orders')
            ->when($q !== '', fn ($w) => $w->where('username', 'like', '%'.addcslashes($q, '%_\\').'%'))
            ->orderBy('username')
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'q'));
    }

    /** Admin menambah saldo pembeli (dipakai selama QRIS maintenance). Butuh konfirmasi ulang password. */
    public function credit(Request $request, User $user, BalanceService $balance)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1000', 'max:'.config('enovaz.admin_credit_max')],
            'note' => ['required', 'string', 'min:3', 'max:120'],
        ], [
            'amount.min' => 'Minimal Rp1.000.',
            'amount.max' => 'Maksimal '.rupiah(config('enovaz.admin_credit_max')).' per transaksi.',
            'note.required' => 'Isi catatan (misalnya: saldo uji pentest).',
        ]);

        if ($user->role !== User::ROLE_PEMBELI) {
            return back()->with('error', 'Saldo hanya untuk akun pembeli.');
        }

        DB::transaction(fn () => $balance->credit($user->id, (int) $data['amount'], BalanceEntry::ADJUSTMENT, null, 'Admin: '.$data['note']));
        Audit::log('admin.balance_credited', $user, ['amount' => (int) $data['amount'], 'note' => mb_substr($data['note'], 0, 120)]);

        return back()->with('status', 'Saldo '.$user->username.' bertambah '.rupiah($data['amount']).'.');
    }

    public function updateRole(Request $request, User $user)
    {
        $data = $request->validate(['role' => ['required', 'in:'.User::ROLE_PEMBELI.','.User::ROLE_ADMIN]]);

        if ($user->is($request->user())) {
            return back()->with('error', 'Kamu tidak bisa mengubah role akunmu sendiri.');
        }

        $from = $user->role;
        if ($from === $data['role']) {
            return back();
        }

        $user->role = $data['role'];
        $user->save();

        Audit::log('user.role_changed', $user, ['from' => $from, 'to' => $data['role']]);

        return back()->with('status', "Role {$user->username} diubah menjadi {$data['role']}.");
    }
}
