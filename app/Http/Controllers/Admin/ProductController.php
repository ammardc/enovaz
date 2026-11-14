<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::withStock()->withSold()->orderByDesc('is_active')->orderBy('name')->paginate(20);

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        return view('admin.products.form', ['product' => new Product(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['image_path'] = $this->storeImage($request);

        $product = Product::create($data);
        Audit::log('product.created', $product, ['name' => $product->name, 'price' => $product->price]);

        return redirect()->route('admin.stock.index', $product)->with('status', 'Produk dibuat. Sekarang tambahkan stok akunnya.');
    }

    public function edit(Product $product)
    {
        return view('admin.products.form', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request);

        if ($path = $this->storeImage($request)) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $path;
        }

        $product->fill($data);
        $changed = array_keys($product->getDirty());
        $product->save();

        Audit::log('product.updated', $product, ['fields' => $changed]);

        return redirect()->route('admin.products.index')->with('status', 'Produk diperbarui.');
    }

    public function toggle(Product $product)
    {
        $product->update(['is_active' => ! $product->is_active]);
        Audit::log($product->is_active ? 'product.activated' : 'product.deactivated', $product);

        return back()->with('status', $product->is_active ? 'Produk diaktifkan.' : 'Produk dinonaktifkan dan disembunyikan dari katalog.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:5000'],
            'price' => ['required', 'integer', 'min:1000', 'max:100000000'],
            'duration' => ['required', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
            // dimensions memakai getimagesize: file yang hanya meniru header gambar ikut ditolak.
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024', 'dimensions:min_width=16,min_height=16,max_width=4000,max_height=4000'],
        ], [
            'image.dimensions' => 'Gambar harus berukuran 16–4000 piksel dan benar-benar berupa gambar.',
        ]);

        unset($data['image']);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    /**
     * Gambar tidak disimpan apa adanya: piksel dibaca ulang lalu ditulis ulang sebagai PNG baru.
     * Sisipan apa pun (kode PHP di ujung file, metadata/EXIF, polyglot) ikut terbuang.
     */
    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $img = @imagecreatefromstring((string) file_get_contents($request->file('image')->getRealPath()));
        if ($img === false) {
            throw ValidationException::withMessages(['image' => 'File bukan gambar yang valid.']);
        }

        // Perkecil bila terlalu besar, sekaligus membuat kanvas baru yang bersih.
        $w = imagesx($img);
        $h = imagesy($img);
        $scale = min(1, 1200 / max($w, $h));
        $clean = imagecreatetruecolor(max(1, (int) round($w * $scale)), max(1, (int) round($h * $scale)));
        imagealphablending($clean, false);
        imagesavealpha($clean, true);
        imagecopyresampled($clean, $img, 0, 0, 0, 0, imagesx($clean), imagesy($clean), $w, $h);

        ob_start();
        imagepng($clean, null, 6);
        $png = (string) ob_get_clean();
        imagedestroy($img);
        imagedestroy($clean);

        $path = 'products/'.Str::random(40).'.png';
        Storage::disk('public')->put($path, $png);

        return $path;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'produk';
        $slug = $base;
        $i = 2;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
