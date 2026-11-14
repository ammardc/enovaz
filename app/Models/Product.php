<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $fillable = ['name', 'slug', 'category', 'description', 'price', 'duration', 'is_active', 'image_path'];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function digitalAccounts(): HasMany
    {
        return $this->hasMany(DigitalAccount::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /** Menambahkan stock_count (akun available). */
    public function scopeWithStock(Builder $q): Builder
    {
        return $q->withCount(['digitalAccounts as stock_count' => fn ($a) => $a->where('status', DigitalAccount::AVAILABLE)]);
    }

    /** Menambahkan sold_count (jumlah item dari pesanan lunas). */
    public function scopeWithSold(Builder $q): Builder
    {
        return $q->withSum(['orderItems as sold_count' => fn ($i) => $i->whereHas('order', fn ($o) => $o->where('status', Order::PAID))], 'quantity');
    }

    public function stock(): int
    {
        return (int) ($this->stock_count ?? $this->digitalAccounts()->where('status', DigitalAccount::AVAILABLE)->count());
    }

    public function sold(): int
    {
        return (int) ($this->sold_count ?? 0);
    }

    public function isNew(): bool
    {
        return $this->created_at && $this->created_at->gt(now()->subDays(14));
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /** Logo merek (public/images/marks/{slug}.svg) bila produk memakai gambar bawaan seeder. */
    public function brandMark(): ?string
    {
        if (! $this->image_path || ! str_starts_with($this->image_path, 'products/')) {
            return null;
        }
        $file = 'images/marks/'.basename($this->image_path, '.svg').'.svg';

        return is_file(public_path($file)) ? asset($file) : null;
    }

    /** Class warna merek (.brand-{slug} di app.css). Class, bukan inline style, supaya lolos CSP produksi. */
    public function brandClass(): string
    {
        return $this->brandMark() ? 'brand-'.basename((string) $this->image_path, '.svg') : 'brand-none';
    }

    /** Nama merek: kata pertama nama produk (ChatGPT Plus → ChatGPT). */
    public function brandName(): string
    {
        return strtok($this->name, ' ') ?: $this->name;
    }

    public function initials(): string
    {
        $words = preg_split('/\s+/', trim($this->name));

        return mb_strtoupper(mb_substr($words[0] ?? 'E', 0, 1).mb_substr($words[1] ?? '', 0, 1));
    }

    /** Warna konsisten per kategori untuk thumbnail tanpa gambar. */
    public function accentClass(): string
    {
        return category_class($this->category);
    }
}
