<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DigitalAccount;
use App\Models\Product;
use App\Services\Audit;
use App\Services\CredentialVault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockController extends Controller
{
    private const MAX_LINES = 200;

    public function index(Product $product)
    {
        $accounts = $product->digitalAccounts()->latest('id')->paginate(30);

        $counts = $product->digitalAccounts()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.stock.index', compact('product', 'accounts', 'counts'));
    }

    /** Tambah stok massal. Format per baris: email_atau_username|password */
    public function store(Request $request, Product $product, CredentialVault $vault)
    {
        $request->validate(['lines' => ['required', 'string', 'max:40000']]);

        $rows = collect(preg_split('/\r\n|\r|\n/', $request->input('lines')))
            ->map(fn ($l) => trim($l))
            ->filter()
            ->values();

        if ($rows->count() > self::MAX_LINES) {
            throw ValidationException::withMessages(['lines' => 'Maksimal '.self::MAX_LINES.' akun per sekali tambah.']);
        }

        $parsed = $rows->map(function ($line, $i) {
            $parts = explode('|', $line, 2);
            if (count($parts) !== 2 || trim($parts[0]) === '' || trim($parts[1]) === '' || mb_strlen($line) > 500) {
                throw ValidationException::withMessages(['lines' => 'Baris '.($i + 1).' tidak sesuai format email|password.']);
            }

            return [trim($parts[0]), trim($parts[1])];
        });

        DB::transaction(function () use ($parsed, $product, $vault) {
            foreach ($parsed as [$email, $password]) {
                DigitalAccount::create([
                    'product_id' => $product->id,
                    'email_enc' => $vault->encrypt($email, CredentialVault::aad($product->id, 'email')),
                    'password_enc' => $vault->encrypt($password, CredentialVault::aad($product->id, 'password')),
                    'status' => DigitalAccount::AVAILABLE,
                ]);
            }
        });

        // Jumlah saja yang dicatat; isi kredensial tidak pernah masuk log.
        Audit::log('stock.added', $product, ['count' => $parsed->count()]);

        return back()->with('status', $parsed->count().' akun ditambahkan dan disimpan terenkripsi.');
    }

    public function disable(Product $product, DigitalAccount $account)
    {
        abort_unless((int) $account->product_id === (int) $product->id, 404);

        if ($account->status !== DigitalAccount::AVAILABLE) {
            return back()->with('error', 'Hanya akun berstatus tersedia yang bisa dinonaktifkan.');
        }

        $account->update(['status' => DigitalAccount::DISABLED]);
        Audit::log('stock.disabled', $account);

        return back()->with('status', 'Akun #'.$account->id.' dinonaktifkan.');
    }
}
