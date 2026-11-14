<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BalanceEntry;
use App\Models\DigitalAccount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;

class DashboardController extends Controller
{
    private const TZ = 'Asia/Jakarta';
    private const CHART_DAYS = 14;

    public function index()
    {
        $today = CarbonImmutable::now(self::TZ)->startOfDay();
        $yesterday = $today->subDay();

        // Penjualan = pesanan produk yang lunas (QRIS atau saldo). Isi saldo bukan penjualan.
        $sales = fn () => Order::where('kind', Order::KIND_PURCHASE)->where('status', Order::PAID);

        $revenueToday = (int) $sales()->where('updated_at', '>=', $today->utc())->sum('total');
        $revenueYesterday = (int) $sales()->whereBetween('updated_at', [$yesterday->utc(), $today->utc()])->sum('total');
        $paidToday = $sales()->where('updated_at', '>=', $today->utc())->count();
        $paidYesterday = $sales()->whereBetween('updated_at', [$yesterday->utc(), $today->utc()])->count();

        $stats = [
            'revenue_today' => $revenueToday,
            'revenue_delta' => $this->delta($revenueToday, $revenueYesterday),
            'paid_today' => $paidToday,
            'paid_delta' => $paidToday - $paidYesterday,
            'topup_today' => (int) BalanceEntry::where('type', BalanceEntry::TOPUP)->where('created_at', '>=', $today->utc())->sum('amount'),
            'balances' => (int) User::where('role', User::ROLE_PEMBELI)->sum('balance'),
            'pending' => Order::where('status', Order::PENDING)->count(),
            'review' => Order::where('status', Order::REVIEW)->count(),
            'stock' => DigitalAccount::where('status', DigitalAccount::AVAILABLE)->count(),
            'buyers' => User::where('role', User::ROLE_PEMBELI)->count(),
        ];

        $chart = $this->dailySales($today);

        // Produk terlaris 30 hari (berdasarkan pendapatan)
        $since = $today->subDays(29)->utc();
        $top = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.kind', Order::KIND_PURCHASE)
            ->where('orders.status', Order::PAID)
            ->where('orders.updated_at', '>=', $since)
            ->groupBy('order_items.product_id')
            ->selectRaw('order_items.product_id, SUM(order_items.quantity) as qty, SUM(order_items.quantity * order_items.unit_price) as revenue')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();
        $topProducts = Product::whereIn('id', $top->pluck('product_id'))->get()->keyBy('id');
        $topMax = (int) max(1, $top->max('revenue'));

        $lowStock = Product::active()->withStock()->get()
            ->filter(fn ($p) => $p->stock() <= config('enovaz.low_stock'))
            ->sortBy(fn ($p) => $p->stock())->take(6);

        $recent = Order::with(['user:id,username', 'items.product:id,name,image_path,category'])->latest()->limit(8)->get();

        return view('admin.dashboard', compact('stats', 'chart', 'top', 'topProducts', 'topMax', 'lowStock', 'recent'));
    }

    /** Persen perubahan; null bila kemarin 0 (tidak ada pembanding). */
    private function delta(int $now, int $before): ?int
    {
        return $before > 0 ? (int) round(($now - $before) / $before * 100) : null;
    }

    /**
     * Penjualan per hari (WIB) untuk grafik kolom, plus skala sumbu Y yang "bulat".
     *
     * @return array{days: array<int, array{date: CarbonImmutable, total: int, count: int}>, max: int, ticks: int[], total: int}
     */
    private function dailySales(CarbonImmutable $today): array
    {
        $start = $today->subDays(self::CHART_DAYS - 1);

        $orders = Order::where('kind', Order::KIND_PURCHASE)
            ->where('status', Order::PAID)
            ->where('updated_at', '>=', $start->utc())
            ->get(['total', 'updated_at']);

        $byDay = $orders->groupBy(fn ($o) => $o->updated_at->copy()->timezone(self::TZ)->format('Y-m-d'));

        $days = [];
        for ($d = $start; $d->lte($today); $d = $d->addDay()) {
            $group = $byDay->get($d->format('Y-m-d'), collect());
            $days[] = ['date' => $d, 'total' => (int) $group->sum('total'), 'count' => $group->count()];
        }

        $peak = max(array_column($days, 'total'));
        $max = $this->niceCeil(max($peak, 1));
        $ticks = [0, (int) ($max / 4), (int) ($max / 2), (int) ($max * 3 / 4), $max];

        return ['days' => $days, 'max' => $max, 'ticks' => $ticks, 'total' => array_sum(array_column($days, 'total'))];
    }

    /** Batas atas sumbu: 1, 2, 4, 5, 8 × 10^n supaya kuartalnya angka bulat. */
    private function niceCeil(int $value): int
    {
        $pow = 10 ** max(0, (int) floor(log10($value)));
        foreach ([1, 2, 4, 5, 8, 10] as $m) {
            if ($m * $pow >= $value) {
                return $m * $pow;
            }
        }

        return 10 * $pow;
    }
}
