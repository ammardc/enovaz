<?php

namespace App\Http\Controllers;

use App\Services\InventoryCipher;
use App\Services\CasakuClient;
use App\Services\PaymentSettlement;
use App\Services\OrderTimeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    private function guard(bool $write = false): void
    {
        $role = auth()->user()?->role;
        abort_unless(in_array($role, $write ? ['owner','admin'] : ['owner','admin','support'], true), 403);
    }

    private function audit(string $action, string $type, ?int $id, array $context = []): void
    {
        DB::table('admin_audit_logs')->insert(['admin_id' => auth()->id(), 'action' => $action, 'subject_type' => $type,
            'subject_id' => $id, 'context' => json_encode($context), 'created_at' => now()]);
    }

    public function index()
    {
        $this->guard();
        $metrics = [
            'sales' => DB::table('orders')->where('status', 'complete')->sum('total_idr'),
            'orders' => DB::table('orders')->count(),
            'pending' => DB::table('payment_invoices')->whereIn('status', ['pending','review'])->count(),
            'wallets' => DB::table('wallet_accounts')->sum('balance_idr'),
        ];
        $recent = DB::table('orders')->orderByDesc('id')->limit(10)->get();
        return view('admin.index', compact('metrics','recent'));
    }

    public function products()
    {
        $this->guard();
        $products = DB::table('products as p')->join('categories as c','c.id','=','p.category_id')
            ->whereNull('p.deleted_at')->select('p.*','c.name as category_name')->orderBy('p.sort_order')->get();
        return view('admin.products', compact('products'));
    }

    public function createProduct(Request $request)
    {
        $this->guard(true);
        $data = $request->validate(['name'=>['required','string','max:120'], 'category_id'=>['required','exists:categories,id']]);
        $slug = Str::slug($data['name']);
        if (DB::table('products')->where('slug',$slug)->exists()) throw ValidationException::withMessages(['name'=>'Nama produk sudah digunakan.']);
        $id = DB::transaction(function () use ($data,$slug) {
            $id = DB::table('products')->insertGetId(['category_id'=>$data['category_id'],'name'=>$data['name'],
                'slug'=>$slug,'status'=>'draft','created_at'=>now(),'updated_at'=>now()]);
            DB::table('product_variants')->insert(['product_id'=>$id,'name'=>'Paket utama','limit_per_customer'=>1,
                'active'=>false,'created_at'=>now(),'updated_at'=>now()]);
            $this->audit('product_created','product',$id);
            return $id;
        });
        return redirect()->route('admin.products.edit',$id);
    }

    public function categories()
    {
        $this->guard();
        $categories = DB::table('categories')->whereNull('deleted_at')->orderBy('sort_order')->get();
        return view('admin.categories',compact('categories'));
    }

    public function createCategory(Request $request)
    {
        $this->guard(true);
        $data = $request->validate(['name'=>['required','string','max:80'],'sort_order'=>['required','integer','min:0','max:9999']]);
        $slug = Str::slug($data['name']);
        if (DB::table('categories')->where('slug',$slug)->exists()) throw ValidationException::withMessages(['name'=>'Kategori sudah ada.']);
        $id = DB::table('categories')->insertGetId(['name'=>$data['name'],'slug'=>$slug,'sort_order'=>$data['sort_order'],
            'active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $this->audit('category_created','category',$id);
        return back()->with('success','Kategori dibuat.');
    }

    public function saveCategory(Request $request, int $id)
    {
        $this->guard(true);
        $data = $request->validate(['name'=>['required','string','max:80'],'sort_order'=>['required','integer','min:0','max:9999'],
            'active'=>['required','in:0,1']]);
        abort_unless(DB::table('categories')->where('id',$id)->whereNull('deleted_at')->exists(),404);
        $slug = Str::slug($data['name']);
        if (DB::table('categories')->where('slug',$slug)->where('id','!=',$id)->exists()) throw ValidationException::withMessages(['name'=>'Slug sudah digunakan.']);
        DB::table('categories')->where('id',$id)->update(['name'=>$data['name'],'slug'=>$slug,
            'sort_order'=>$data['sort_order'],'active'=>$data['active']==='1','updated_at'=>now()]);
        $this->audit('category_updated','category',$id,['active'=>$data['active']]);
        return back()->with('success','Kategori diperbarui.');
    }

    public function content()
    {
        $this->guard();
        $pages = DB::table('content_pages')->orderBy('id')->get();
        return view('admin.content',compact('pages'));
    }

    public function saveContent(Request $request, string $slug)
    {
        $this->guard(true);
        $data = $request->validate(['title'=>['required','string','max:120'],'body'=>['required','string','max:30000'],
            'published'=>['required','in:0,1']]);
        $page = DB::table('content_pages')->where('slug',$slug)->first();
        abort_unless($page,404);
        $version = now()->format('YmdHis').'-'.Str::lower(Str::random(5));
        DB::transaction(function () use ($page,$data,$version) {
            DB::table('content_pages')->where('id',$page->id)->update(['title'=>$data['title'],'body'=>$data['body'],
                'version'=>$version,'published'=>$data['published']==='1','updated_at'=>now()]);
            DB::table('policy_versions')->insert(['slug'=>$page->slug,'version'=>$version,'body'=>$data['body'],
                'admin_id'=>auth()->id(),'created_at'=>now()]);
            $this->audit('content_updated','content',$page->id,['version'=>$version]);
        });
        return back()->with('success','Konten disimpan sebagai versi '.$version.'.');
    }

    public function editProduct(int $id)
    {
        $this->guard();
        $product = DB::table('products')->where('id',$id)->whereNull('deleted_at')->first();
        abort_unless($product,404);
        $variant = DB::table('product_variants')->where('product_id',$id)->orderBy('id')->first();
        $categories = DB::table('categories')->whereNull('deleted_at')->orderBy('sort_order')->get();
        $stock = $variant ? DB::table('inventory_units')->where('variant_id',$variant->id)->where('status','available')->count() : 0;
        $media = DB::table('product_media')->where('product_id',$id)->orderByDesc('primary')->orderByDesc('id')->get();
        return view('admin.product-edit',compact('product','variant','categories','stock','media'));
    }

    public function saveProduct(Request $request, int $id)
    {
        $this->guard(true);
        $data = $request->validate([
            'name'=>['required','string','max:120'], 'category_id'=>['required','exists:categories,id'],
            'status'=>['required','in:draft,coming_soon,published,hidden'],
            'description'=>['nullable','string','max:10000'],
            'usage_instructions'=>['nullable','string','max:5000'],
            'variant_name'=>['required','string','max:120'],
            'price_idr'=>['nullable','integer','min:1000','max:100000000'], 'limit_per_customer'=>['required','integer','min:1','max:20'],
            'badge'=>['nullable','in:HOT,NEW'],
        ]);
        $product = DB::table('products')->where('id',$id)->whereNull('deleted_at')->first();
        abort_unless($product,404);
        $variant = DB::table('product_variants')->where('product_id',$id)->first();
        if ($data['status'] === 'published') {
            foreach (['description','usage_instructions','price_idr'] as $key) {
                if (empty($data[$key])) throw ValidationException::withMessages([$key => 'Lengkapi semua detail sebelum produk diterbitkan.']);
            }
            if (! $variant || DB::table('inventory_units')->where('variant_id',$variant->id)->where('status','available')->count() < 1) {
                throw ValidationException::withMessages(['stock' => 'Masukkan stok sebelum menerbitkan produk.']);
            }
        }
        DB::transaction(function () use ($id,$variant,$data) {
            DB::table('products')->where('id',$id)->update([
                'name'=>$data['name'],'category_id'=>$data['category_id'],'status'=>$data['status'],
                'summary'=>null,'description'=>$data['description']??null,
                'access_terms'=>null,'refund_terms'=>null,
                'usage_instructions'=>$data['usage_instructions']??null,'badge'=>$data['badge']??null,'updated_at'=>now(),
            ]);
            DB::table('product_variants')->where('id',$variant->id)->update([
                'name'=>$data['variant_name'],
                'price_idr'=>$data['price_idr']??null,'limit_per_customer'=>$data['limit_per_customer'],
                'active'=>$data['status']==='published','updated_at'=>now(),
            ]);
            $this->audit('product_updated','product',$id,['status'=>$data['status'],'price_idr'=>$data['price_idr']??null]);
        });
        return back()->with('success','Produk diperbarui.');
    }

    public function uploadMedia(Request $request, int $id)
    {
        $this->guard(true);
        abort_unless(DB::table('products')->where('id',$id)->whereNull('deleted_at')->exists(),404);
        $data = $request->validate(['image'=>['required','image','mimes:jpeg,png,webp','max:3072'],
            'alt'=>['required','string','max:200']]);
        if (! extension_loaded('gd')) throw ValidationException::withMessages(['image'=>'Ekstensi PHP GD diperlukan untuk memproses foto.']);
        $file = $request->file('image');
        $info = getimagesize($file->getRealPath());
        if (! $info || $info[0] * $info[1] > 20000000) throw ValidationException::withMessages(['image'=>'Dimensi gambar terlalu besar.']);
        $source = match ($info['mime']) {
            'image/jpeg'=>imagecreatefromjpeg($file->getRealPath()),
            'image/png'=>imagecreatefrompng($file->getRealPath()),
            'image/webp'=>imagecreatefromwebp($file->getRealPath()),
            default=>false,
        };
        if (! $source) throw ValidationException::withMessages(['image'=>'Gambar tidak dapat dibaca.']);
        $width = min($info[0],1200); $height = max(1,(int) round($info[1] * $width / $info[0]));
        $resized = imagescale($source,$width,$height);
        imagedestroy($source);
        if (! $resized) throw ValidationException::withMessages(['image'=>'Gambar tidak dapat diproses.']);
        $directory = public_path('uploads/products');
        if (! is_dir($directory)) mkdir($directory,0755,true);
        $path = 'uploads/products/'.Str::uuid().'.webp';
        $saved = imagewebp($resized,public_path($path),82);
        imagedestroy($resized);
        if (! $saved) throw ValidationException::withMessages(['image'=>'Gagal menyimpan gambar.']);
        try {
            DB::transaction(function () use ($id,$path,$data) {
                DB::table('product_media')->where('product_id',$id)->update(['primary'=>false,'updated_at'=>now()]);
                $mediaId = DB::table('product_media')->insertGetId(['product_id'=>$id,'path'=>$path,'alt'=>$data['alt'],
                    'primary'=>true,'created_at'=>now(),'updated_at'=>now()]);
                $this->audit('media_uploaded','media',$mediaId,['product_id'=>$id]);
            });
        } catch (\Throwable $e) { @unlink(public_path($path)); throw $e; }
        return back()->with('success','Gambar produk ditambahkan.');
    }

    public function editMedia(int $id)
    {
        $this->guard();
        $product = DB::table('products')->where('id',$id)->whereNull('deleted_at')->first();
        abort_unless($product,404);
        $media = DB::table('product_media')->where('product_id',$id)->orderByDesc('primary')->orderByDesc('id')->get();
        return view('admin.media',compact('product','media'));
    }

    public function variants(int $id)
    {
        $this->guard();
        $product = DB::table('products')->where('id',$id)->whereNull('deleted_at')->first();
        abort_unless($product,404);
        $variants = DB::table('product_variants as v')->where('product_id',$id)->select('v.*')
            ->selectSub(function ($q) {
                $q->from('inventory_units')->selectRaw('COUNT(*)')->whereColumn('variant_id','v.id')->where('status','available');
            },'stock_count')->orderBy('id')->get();
        return view('admin.variants',compact('product','variants'));
    }

    public function createVariant(Request $request, int $id)
    {
        $this->guard(true);
        $data = $request->validate(['name'=>['required','string','max:120']]);
        abort_unless(DB::table('products')->where('id',$id)->whereNull('deleted_at')->exists(),404);
        $variantId = DB::table('product_variants')->insertGetId(['product_id'=>$id,'name'=>$data['name'],
            'active'=>false,'limit_per_customer'=>1,'created_at'=>now(),'updated_at'=>now()]);
        $this->audit('variant_created','variant',$variantId,['product_id'=>$id]);
        return back()->with('success','Varian draft dibuat.');
    }

    public function saveVariant(Request $request, int $id)
    {
        $this->guard(true);
        $data = $request->validate(['name'=>['required','string','max:120'],
            'price_idr'=>['nullable','integer','min:1000','max:100000000'],'limit_per_customer'=>['required','integer','min:1','max:20'],
            'active'=>['required','in:0,1']]);
        $variant = DB::table('product_variants')->where('id',$id)->first();
        abort_unless($variant,404);
        if ($data['active']==='1') {
            if (empty($data['price_idr'])) throw ValidationException::withMessages(['price_idr'=>'Isi harga sebelum paket diaktifkan.']);
            $product = DB::table('products')->where('id',$variant->product_id)->first();
            if ($product->status!=='published') throw ValidationException::withMessages(['active'=>'Terbitkan produk lebih dulu.']);
            if (! DB::table('inventory_units')->where('variant_id',$id)->where('status','available')->exists()) throw ValidationException::withMessages(['active'=>'Tambahkan stok lebih dulu.']);
        }
        DB::table('product_variants')->where('id',$id)->update(['name'=>$data['name'],'price_idr'=>$data['price_idr']??null,
            'limit_per_customer'=>$data['limit_per_customer'],'active'=>$data['active']==='1','updated_at'=>now()]);
        $this->audit('variant_updated','variant',$id,['price_idr'=>$data['price_idr']??null,'active'=>$data['active']]);
        return back()->with('success','Varian diperbarui.');
    }

    public function deleteMedia(int $id)
    {
        $this->guard(true);
        $media = DB::table('product_media')->where('id',$id)->first();
        abort_unless($media,404);
        DB::transaction(function () use ($media) {
            DB::table('product_media')->where('id',$media->id)->delete();
            if ($media->primary) {
                $next = DB::table('product_media')->where('product_id',$media->product_id)->orderByDesc('id')->first();
                if ($next) DB::table('product_media')->where('id',$next->id)->update(['primary'=>true,'updated_at'=>now()]);
            }
            $this->audit('media_deleted','media',$media->id,['product_id'=>$media->product_id]);
        });
        $path = public_path($media->path);
        if (is_file($path)) @unlink($path);
        return back()->with('success','Gambar dihapus.');
    }

    public function stockIndex(Request $request)
    {
        $this->guard();
        $data = $request->validate(['produk'=>['nullable','integer','exists:products,id']]);
        $available = DB::table('inventory_units')->where('status','available')
            ->select('variant_id',DB::raw('COUNT(*) as total'))->groupBy('variant_id');
        $packages = DB::table('product_variants as v')->join('products as p','p.id','=','v.product_id')
            ->leftJoinSub($available,'stock','stock.variant_id','=','v.id')
            ->whereNull('p.deleted_at')
            ->when(isset($data['produk']),fn ($query) => $query->where('p.id',$data['produk']))
            ->select('v.id','v.name as variant_name','p.name as product_name','p.status as product_status',
                'v.active',DB::raw('COALESCE(stock.total, 0) as available'))
            ->orderBy('p.sort_order')->orderBy('p.name')->orderBy('v.id')->paginate(30);
        return view('admin.stock-index',compact('packages'));
    }

    public function stock(int $variant)
    {
        $this->guard();
        $package = DB::table('product_variants as v')->join('products as p','p.id','=','v.product_id')
            ->where('v.id',$variant)->select('v.*','p.name as product_name')->first();
        abort_unless($package,404);
        $counts = DB::table('inventory_units')->where('variant_id',$variant)->select('status',DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total','status');
        $units = DB::table('inventory_units')->where('variant_id',$variant)->select('id','status','fingerprint','order_id','created_at')
            ->orderByDesc('id')->paginate(25);
        return view('admin.stock',compact('package','counts','units'));
    }

    public function addStock(Request $request, int $variant, InventoryCipher $cipher)
    {
        $this->guard(true);
        abort_unless(DB::table('product_variants')->where('id',$variant)->exists(),404);
        $data = $request->validate(['code'=>['nullable','string','max:500'],
            'account'=>['nullable','string','max:500'],'password'=>['nullable','string','max:500'],
            'instructions'=>['nullable','string','max:2000']]);
        $unit = [];
        foreach (['code','account','password','instructions'] as $key) $unit[$key] = trim((string) ($data[$key] ?? ''));
        if ($unit['code'] === '' && $unit['account'] === '') {
            throw ValidationException::withMessages(['code'=>'Isi kode atau akun.']);
        }
        if ($unit['account'] !== '' && $unit['password'] === '') {
            throw ValidationException::withMessages(['password'=>'Kata sandi akun wajib diisi.']);
        }
        $fingerprint = $cipher->fingerprint($unit);
        try {
            DB::transaction(function () use ($variant,$fingerprint,$unit,$cipher) {
                $id = DB::table('inventory_units')->insertGetId(['variant_id'=>$variant,
                    'fingerprint'=>$fingerprint,'ciphertext'=>$cipher->encrypt($unit),
                    'status'=>'available','created_at'=>now(),'updated_at'=>now()]);
                $this->audit('stock_added','inventory_unit',$id,['variant_id'=>$variant]);
            });
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['code'=>'Kode atau akun ini sudah ada dalam stok.']);
        }
        return back()->with('success','Satu unit stok berhasil ditambahkan.');
    }

    public function importStock(Request $request, int $variant, InventoryCipher $cipher)
    {
        $this->guard(true);
        abort_unless(DB::table('product_variants')->where('id',$variant)->exists(),404);
        $request->validate(['file'=>['required','file','mimes:csv,txt','max:1024'], 'action'=>['required','in:preview,import']]);
        $rows = []; $errors = []; $seen = [];
        $handle = fopen($request->file('file')->getRealPath(),'rb');
        $header = fgetcsv($handle);
        if ($header !== ['code','account','password','instructions']) throw ValidationException::withMessages(['file'=>'Header CSV harus: code,account,password,instructions']);
        $line = 1;
        while (($fields = fgetcsv($handle)) !== false) {
            $line++;
            if ($line > 501) { $errors[] = 'Maksimal 500 baris per impor.'; break; }
            $unit = array_combine($header, array_pad(array_slice($fields,0,4),4,''));
            $unit = array_map(fn ($value) => trim((string) $value),$unit);
            if (! $unit['code'] && ! $unit['account']) { $errors[] = "Baris $line: kode atau akun wajib diisi."; continue; }
            if ($unit['account'] && ! $unit['password']) { $errors[] = "Baris $line: password akun wajib diisi."; continue; }
            $fingerprint = $cipher->fingerprint($unit);
            if (isset($seen[$fingerprint]) || DB::table('inventory_units')->where('fingerprint',$fingerprint)->exists()) { $errors[] = "Baris $line: stok duplikat."; continue; }
            $seen[$fingerprint] = true;
            $rows[] = ['variant_id'=>$variant,'fingerprint'=>$fingerprint,'ciphertext'=>$cipher->encrypt($unit),
                'status'=>'available','created_at'=>now(),'updated_at'=>now()];
        }
        fclose($handle);
        if ($request->input('action') === 'preview') return back()->with('preview', ['valid'=>count($rows),'errors'=>$errors]);
        if ($errors) throw ValidationException::withMessages(['file'=>implode(' ',array_slice($errors,0,5))]);
        if (! $rows) throw ValidationException::withMessages(['file'=>'Tidak ada stok valid.']);
        DB::transaction(function () use ($rows,$variant) {
            DB::table('inventory_units')->insert($rows);
            $this->audit('stock_imported','variant',$variant,['count'=>count($rows)]);
        });
        return back()->with('success',count($rows).' unit stok berhasil dimasukkan.');
    }

    public function changeStockStatus(Request $request, int $variant, int $unit)
    {
        $this->guard(true);
        $data = $request->validate(['status'=>['required','in:disabled,quarantined,available'],
            'reason'=>['required','string','min:5','max:1000']]);
        DB::transaction(function () use ($variant,$unit,$data) {
            $stock = DB::table('inventory_units')->where('id',$unit)->where('variant_id',$variant)->lockForUpdate()->first();
            abort_unless($stock,404);
            if (! in_array($stock->status,['available','disabled','quarantined'],true)) throw ValidationException::withMessages(['status'=>'Stok yang dipesan atau terkirim tidak dapat diubah.']);
            DB::table('inventory_units')->where('id',$unit)->update(['status'=>$data['status'],'updated_at'=>now()]);
            $this->audit('stock_status_changed','inventory_unit',$unit,['from'=>$stock->status,'to'=>$data['status'],'reason'=>$data['reason']]);
        });
        return back()->with('success','Status unit stok diperbarui.');
    }

    public function revealStock(Request $request, int $variant, int $unit, InventoryCipher $cipher)
    {
        abort_unless(auth()->user()?->role==='owner',403);
        $data = $request->validate(['reason'=>['required','string','min:5','max:1000']]);
        $stock = DB::table('inventory_units')->where('id',$unit)->where('variant_id',$variant)->first();
        abort_unless($stock,404);
        $this->audit('stock_revealed','inventory_unit',$unit,['reason'=>$data['reason']]);
        $access = $cipher->decrypt($stock->ciphertext);
        return response()->view('admin.stock-reveal',compact('stock','access'))->header('Cache-Control','private, no-store, max-age=0');
    }

    public function orders()
    {
        $this->guard();
        $orders = DB::table('orders as o')->join('users as u','u.id','=','o.user_id')
            ->select('o.*','u.email')->orderByDesc('o.id')->paginate(25);
        return view('admin.orders',compact('orders'));
    }

    public function payments()
    {
        $this->guard();
        $payments = DB::table('payment_invoices as i')->join('users as u','u.id','=','i.user_id')
            ->select('i.*','u.email')->orderByDesc('i.id')->paginate(25);
        return view('admin.payments',compact('payments'));
    }

    public function checkPayment(int $id, CasakuClient $casaku, PaymentSettlement $settlement)
    {
        $this->guard(true);
        $invoice = DB::table('payment_invoices')->where('id',$id)->first();
        abort_unless($invoice,404);
        if (! $invoice->provider_transaction_id) return back()->withErrors(['payment'=>'ID transaksi Casaku belum diketahui; periksa di dashboard Casaku.']);
        try {
            $status = $casaku->status($invoice->provider_transaction_id);
            if (data_get($status,'status') === 'paid') {
                $settlement->paid($invoice->provider_transaction_id,(int) data_get($status,'amount'),
                    data_get($status,'paidAt'),'admin-check:'.hash('sha256',json_encode($status)));
            } elseif (in_array(data_get($status,'status'),['expired','cancel'],true)) {
                $settlement->expired($invoice->provider_transaction_id);
            }
            $this->audit('payment_rechecked','invoice',$id,['provider_status'=>data_get($status,'status')]);
            return back()->with('success','Status Casaku diperiksa.');
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['payment'=>'Casaku belum dapat memastikan status invoice.']);
        }
    }

    public function reports(Request $request)
    {
        $this->guard();
        return view('admin.reports', $this->reportData($request));
    }

    private function reportData(Request $request): array
    {
        $data = $request->validate(['from'=>['nullable','date'],'to'=>['nullable','date','after_or_equal:from']]);
        $from = $data['from'] ?? now()->startOfMonth()->toDateString();
        $to = $data['to'] ?? now()->toDateString();
        $paid = DB::table('orders')->where('status','complete')->whereDate('paid_at','>=',$from)->whereDate('paid_at','<=',$to);
        $summary = ['sales'=>(clone $paid)->sum('total_idr'),'orders'=>(clone $paid)->count(),
            'topups'=>DB::table('payment_invoices')->where('purpose','wallet_topup')->where('status','paid')
                ->whereDate('paid_at','>=',$from)->whereDate('paid_at','<=',$to)->sum('base_amount_idr'),
            'refunds'=>DB::table('refunds')->where('status','completed')->whereDate('updated_at','>=',$from)->whereDate('updated_at','<=',$to)->sum('amount_idr'),
            'wallet_spent'=>-DB::table('wallet_ledger_entries')->where('kind','purchase')->whereDate('created_at','>=',$from)
                ->whereDate('created_at','<=',$to)->sum('amount_idr'),
            'wallet_outstanding'=>DB::table('wallet_accounts')->sum('balance_idr')];
        $summary['net_sales'] = $summary['sales'] - $summary['refunds'];
        $products = DB::table('order_items as i')->join('orders as o','o.id','=','i.order_id')
            ->where('o.status','complete')->whereDate('o.paid_at','>=',$from)->whereDate('o.paid_at','<=',$to)
            ->select('i.product_name',DB::raw('SUM(i.quantity) as units'),DB::raw('SUM(i.quantity * i.unit_price_idr) as revenue'))
            ->groupBy('i.product_name')->orderByDesc('revenue')->get();
        return compact('summary','products','from','to');
    }

    public function exportReports(Request $request)
    {
        $this->guard();
        ['summary'=>$summary,'products'=>$products,'from'=>$from,'to'=>$to] = $this->reportData($request);
        $this->audit('report_exported','report',null,['from'=>$from,'to'=>$to]);
        return response()->streamDownload(function () use ($summary,$products,$from,$to) {
            $out = fopen('php://output','w');
            fwrite($out,"\xEF\xBB\xBF");
            fputcsv($out,['Laporan E-Novaz',$from,$to]);
            fputcsv($out,['Metrik','Rupiah atau jumlah']);
            foreach (['sales'=>'Penjualan selesai','refunds'=>'Refund selesai','net_sales'=>'Penjualan bersih',
                'orders'=>'Order selesai','topups'=>'Top up sah','wallet_spent'=>'Pemakaian saldo',
                'wallet_outstanding'=>'Saldo outstanding saat ekspor'] as $key=>$label) {
                fputcsv($out,[$label,$summary[$key]]);
            }
            fputcsv($out,[]);
            fputcsv($out,['Produk','Unit terjual','Nilai rupiah']);
            foreach ($products as $row) {
                $name = (string) $row->product_name;
                if (preg_match('/^[\s\x00-\x1F]*[=+\-@]/u',$name)) $name = "'".$name;
                fputcsv($out,[$name,$row->units,$row->revenue]);
            }
            fclose($out);
        },"laporan-enovaz-{$from}-{$to}.csv",['Content-Type'=>'text/csv; charset=UTF-8']);
    }

    public function auditLogs()
    {
        $this->guard();
        $logs = DB::table('admin_audit_logs as a')->leftJoin('users as u','u.id','=','a.admin_id')
            ->select('a.*','u.email')->orderByDesc('a.id')->paginate(30);
        return view('admin.audit',compact('logs'));
    }

    public function refunds()
    {
        $this->guard();
        $refunds = DB::table('refunds as r')->join('orders as o','o.id','=','r.order_id')
            ->join('users as u','u.id','=','r.user_id')->select('r.*','o.number','u.email')->orderByDesc('r.id')->paginate(25);
        return view('admin.refunds',compact('refunds'));
    }

    public function decideRefund(Request $request, int $id)
    {
        $this->guard(true);
        $data = $request->validate(['action'=>['required','in:reject,wallet,manual_pending,manual_complete'],
            'reason'=>['required','string','min:5','max:2000'],'evidence'=>['nullable','string','max:2000']]);
        DB::transaction(function () use ($id,$data) {
            $refund = DB::table('refunds')->where('id',$id)->lockForUpdate()->first();
            abort_unless($refund,404);
            $order = DB::table('orders')->where('id',$refund->order_id)->lockForUpdate()->first();
            $action = $data['action'];
            if ($action === 'manual_complete' && $refund->status !== 'manual_pending') throw ValidationException::withMessages(['action'=>'Mulai proses manual terlebih dahulu.']);
            if ($action !== 'manual_complete' && $refund->status !== 'requested') throw ValidationException::withMessages(['action'=>'Pengajuan ini sudah diproses.']);
            $completed = DB::table('refunds')->where('order_id',$order->id)->where('status','completed')->sum('amount_idr');
            if ($completed + $refund->amount_idr > $order->total_idr) throw ValidationException::withMessages(['action'=>'Total refund melebihi pembayaran sah.']);
            if ($action === 'wallet') {
                $wallet = DB::table('wallet_accounts')->where('user_id',$refund->user_id)->lockForUpdate()->first();
                abort_unless($wallet,422);
                $after = $wallet->balance_idr + $refund->amount_idr;
                DB::table('wallet_accounts')->where('id',$wallet->id)->update(['balance_idr'=>$after,'updated_at'=>now()]);
                DB::table('wallet_ledger_entries')->insert(['wallet_account_id'=>$wallet->id,'kind'=>'refund',
                    'amount_idr'=>$refund->amount_idr,'balance_after_idr'=>$after,'reference'=>'refund:'.$refund->id,
                    'reason'=>$data['reason'],'created_at'=>now(),'updated_at'=>now()]);
            }
            if ($action === 'manual_complete' && empty($data['evidence'])) throw ValidationException::withMessages(['evidence'=>'Catat bukti pengembalian manual.']);
            $status = match ($action) {'reject'=>'rejected','manual_pending'=>'manual_pending',default=>'completed'};
            $method = match ($action) {'wallet'=>'wallet','manual_pending','manual_complete'=>'original_manual',default=>$refund->method};
            DB::table('refunds')->where('id',$id)->update(['status'=>$status,'method'=>$method,'admin_id'=>auth()->id(),
                'reason'=>$refund->reason."\nKeputusan: ".$data['reason'],'evidence'=>$data['evidence']??$refund->evidence,'updated_at'=>now()]);
            DB::table('orders')->where('id',$order->id)->update(['status'=>$status==='completed'?'refunded':($status==='rejected'?'complete':'refund_processing'),'updated_at'=>now()]);
            OrderTimeline::record($order->id,$status==='completed'?'refunded':($status==='rejected'?'complete':'refund_processing'),'Keputusan refund: '.$status.'.');
            $this->audit('refund_'.$status,'refund',$id,['method'=>$method,'amount_idr'=>$refund->amount_idr]);
        });
        return back()->with('success','Refund diperbarui.');
    }

    public function customers(Request $request)
    {
        $this->guard();
        $term = trim((string) $request->query('q',''));
        $customers = DB::table('users as u')->leftJoin('wallet_accounts as w','w.user_id','=','u.id')
            ->where('u.role','customer')->when($term !== '',fn ($q) => $q->where(function ($x) use ($term) {
                $x->where('u.email','like','%'.$term.'%')->orWhere('u.name','like','%'.$term.'%');
            }))->select('u.id','u.name','u.email','u.email_verified_at','u.blocked','w.balance_idr')
            ->orderByDesc('u.id')->paginate(25)->withQueryString();
        return view('admin.customers',compact('customers','term'));
    }

    public function customerStatus(Request $request, int $id)
    {
        $this->guard(true);
        $data = $request->validate(['blocked'=>['required','in:0,1'],'reason'=>['required','string','min:5','max:1000']]);
        $user = DB::table('users')->where('id',$id)->where('role','customer')->first();
        abort_unless($user,404);
        DB::table('users')->where('id',$id)->update(['blocked'=>$data['blocked']==='1','updated_at'=>now()]);
        $this->audit($data['blocked']==='1'?'customer_blocked':'customer_unblocked','user',$id,['reason'=>$data['reason']]);
        return back()->with('success','Status pelanggan diperbarui.');
    }

    public function adjustments()
    {
        $this->guard();
        $adjustments = DB::table('admin_adjustments as a')->join('users as u','u.id','=','a.user_id')
            ->join('users as r','r.id','=','a.requested_by')->select('a.*','u.email as customer_email','r.email as requester_email')
            ->orderByDesc('a.id')->paginate(25);
        return view('admin.adjustments',compact('adjustments'));
    }

    public function requestAdjustment(Request $request)
    {
        $this->guard(true);
        $data = $request->validate(['email'=>['required','email'],'amount_idr'=>['required','integer','between:-100000000,100000000','not_in:0'],
            'reason'=>['required','string','min:10','max:2000']]);
        $user = DB::table('users')->where('email',$data['email'])->where('role','customer')->first();
        if (! $user) throw ValidationException::withMessages(['email'=>'Pelanggan tidak ditemukan.']);
        $id = DB::table('admin_adjustments')->insertGetId(['user_id'=>$user->id,'requested_by'=>auth()->id(),
            'amount_idr'=>$data['amount_idr'],'reason'=>$data['reason'],'status'=>'pending','created_at'=>now(),'updated_at'=>now()]);
        $this->audit('adjustment_requested','adjustment',$id,['amount_idr'=>$data['amount_idr']]);
        return back()->with('success','Koreksi menunggu persetujuan admin kedua.');
    }

    public function decideAdjustment(Request $request, int $id)
    {
        $this->guard(true);
        $data = $request->validate(['decision'=>['required','in:approve,reject'],'reason'=>['required','string','min:5','max:2000']]);
        DB::transaction(function () use ($id,$data) {
            $adjustment = DB::table('admin_adjustments')->where('id',$id)->lockForUpdate()->first();
            abort_unless($adjustment,404);
            if ($adjustment->status !== 'pending') throw ValidationException::withMessages(['decision'=>'Koreksi sudah diputuskan.']);
            if ($adjustment->requested_by === auth()->id()) throw ValidationException::withMessages(['decision'=>'Pengusul tidak boleh menyetujui koreksinya sendiri.']);
            if ($data['decision'] === 'approve') {
                $wallet = DB::table('wallet_accounts')->where('user_id',$adjustment->user_id)->lockForUpdate()->first();
                abort_unless($wallet,422);
                $after = $wallet->balance_idr + $adjustment->amount_idr;
                if ($after < 0) throw ValidationException::withMessages(['decision'=>'Saldo tidak boleh menjadi negatif.']);
                DB::table('wallet_accounts')->where('id',$wallet->id)->update(['balance_idr'=>$after,'updated_at'=>now()]);
                DB::table('wallet_ledger_entries')->insert(['wallet_account_id'=>$wallet->id,'kind'=>'adjustment',
                    'amount_idr'=>$adjustment->amount_idr,'balance_after_idr'=>$after,'reference'=>'adjustment:'.$id,
                    'reason'=>$adjustment->reason.' | Disetujui: '.$data['reason'],'created_at'=>now(),'updated_at'=>now()]);
            }
            $status = $data['decision']==='approve'?'approved':'rejected';
            DB::table('admin_adjustments')->where('id',$id)->update(['status'=>$status,'approved_by'=>auth()->id(),
                'decision_reason'=>$data['reason'],'updated_at'=>now()]);
            $this->audit('adjustment_'.$status,'adjustment',$id,['amount_idr'=>$adjustment->amount_idr]);
        },3);
        return back()->with('success','Keputusan koreksi saldo disimpan.');
    }
}
