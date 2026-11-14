<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('code', 32)->unique(); // nomor pesanan yang tampil ke pembeli
            $table->decimal('total', 12, 0);
            $table->enum('status', ['pending', 'paid', 'expired', 'review_required', 'cancelled'])->default('pending');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index(['user_id', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->unsignedSmallInteger('quantity');
            $table->decimal('unit_price', 12, 0); // harga historis saat checkout
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE orders ADD CONSTRAINT chk_orders_total CHECK (total > 0)');
            DB::statement('ALTER TABLE order_items ADD CONSTRAINT chk_items_qty CHECK (quantity > 0)');
            DB::statement('ALTER TABLE order_items ADD CONSTRAINT chk_items_price CHECK (unit_price > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
