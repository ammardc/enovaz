<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Ambil pesanan milik pengguna yang sedang login.
     * ID dari URL dianggap input tak tepercaya; query selalu dibatasi user_id dari sesi (cegah IDOR).
     */
    protected function ownedOrder(Request $request, string $code): Order
    {
        return Order::where('code', $code)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
    }
}
