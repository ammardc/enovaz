<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('category', 50)->index();
            $table->text('description');
            $table->decimal('price', 12, 0);
            $table->string('duration', 50);
            $table->boolean('is_active')->default(true);
            $table->string('image_path')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE products ADD CONSTRAINT chk_products_price CHECK (price > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
