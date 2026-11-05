<div class="panel" style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:18px">
    <div style="display:flex;align-items:center;gap:14px">
        @if($media->first())
            <img src="{{ asset($media->first()->path) }}" alt="{{ $media->first()->alt }}" style="width:72px;height:62px;object-fit:contain;background:#fff;border:1px solid #ece7f2;border-radius:10px">
        @endif
        <div><strong>Gambar produk</strong><div class="small muted">{{ $media->first() ? 'Gambar utama terpasang' : 'Belum ada gambar' }}</div></div>
    </div>
    <a class="button secondary" href="{{ route('admin.products.media.edit',$product->id) }}">Ganti gambar</a>
</div>
