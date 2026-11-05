<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\OrderTimeline;

class RefundController extends Controller
{
    public function request(Request $request, string $number)
    {
        $data = $request->validate(['reason'=>['required','string','min:10','max:2000']]);
        $order = DB::table('orders')->where('number',$number)->where('user_id',auth()->id())->where('status','complete')->first();
        abort_unless($order,404);
        if (DB::table('refunds')->where('order_id',$order->id)->whereIn('status',['requested','manual_pending','completed'])->exists()) {
            throw ValidationException::withMessages(['reason'=>'Pengajuan refund untuk pesanan ini sudah ada.']);
        }
        DB::table('refunds')->insert(['order_id'=>$order->id,'user_id'=>auth()->id(),'amount_idr'=>$order->total_idr,
            'method'=>'undecided','status'=>'requested','reason'=>$data['reason'],'created_at'=>now(),'updated_at'=>now()]);
        DB::table('orders')->where('id',$order->id)->update(['status'=>'refund_processing','updated_at'=>now()]);
        OrderTimeline::record($order->id,'refund_processing','Pengajuan refund diterima untuk ditinjau.');
        return back()->with('success','Pengajuan refund diterima untuk ditinjau admin.');
    }
}
