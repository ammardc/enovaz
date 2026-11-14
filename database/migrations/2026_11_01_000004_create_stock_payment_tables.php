<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->text('email_enc');    // v1:base64(nonce || ciphertext || tag)
            $table->text('password_enc');
            $table->enum('status', ['available', 'reserved', 'sold', 'disabled'])->default('available');
            $table->foreignId('reserved_order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->dateTime('reserved_until')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index(['product_id', 'status']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->restrictOnDelete();
            $table->string('gateway_transaction_id', 64)->nullable()->unique();
            $table->decimal('base_amount', 12, 0);
            $table->decimal('expected_amount', 12, 0); // nominal final termasuk kode unik
            $table->text('qr_string')->nullable();
            $table->enum('status', ['pending', 'paid', 'expired', 'review_required', 'refunded'])->default('pending');
            $table->dateTime('expires_at');
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index(['status', 'expires_at']);
        });

        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->restrictOnDelete();
            $table->foreignId('digital_account_id')->unique()->constrained('digital_accounts')->restrictOnDelete();
            $table->dateTime('delivered_at');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 64)->index();
            $table->string('target_type', 64)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('ip', 45)->nullable();
            $table->json('meta')->nullable(); // tidak pernah berisi rahasia
            $table->dateTime('created_at');
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE payments ADD CONSTRAINT chk_pay_amount CHECK (base_amount > 0 AND expected_amount >= base_amount)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('digital_accounts');
    }
};
