<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class OrderTimeline
{
    public static function record(int $orderId, string $status, ?string $note = null): void
    {
        DB::table('order_status_events')->insert(['order_id'=>$orderId,'status'=>$status,'note'=>$note,'created_at'=>now()]);
    }
}
