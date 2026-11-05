<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('customer')->index();
            $table->boolean('blocked')->default(false);
            $table->timestamp('last_login_at')->nullable();
        });
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_id');
            $table->timestamps();
            $table->unique(['provider', 'provider_id']);
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('draft')->index();
            $table->string('badge')->nullable();
            $table->string('art_theme')->default('violet');
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->text('usage_instructions')->nullable();
            $table->text('access_terms')->nullable();
            $table->text('refund_terms')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('duration_days')->nullable();
            $table->string('access_type')->nullable();
            $table->string('region')->nullable();
            $table->unsignedBigInteger('price_idr')->nullable();
            $table->unsignedInteger('limit_per_customer')->default(1);
            $table->boolean('active')->default(false);
            $table->timestamps();
        });
        Schema::create('inventory_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->string('fingerprint', 64)->unique();
            $table->longText('ciphertext');
            $table->string('status')->default('available')->index();
            $table->foreignId('order_id')->nullable()->index();
            $table->timestamp('reserved_until')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status')->index();
            $table->string('payment_method');
            $table->unsignedBigInteger('total_idr');
            $table->string('policy_version')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
        Schema::table('inventory_units', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
        });
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('product_name');
            $table->string('variant_name');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price_idr');
            $table->json('terms_snapshot')->nullable();
            $table->timestamps();
        });
        Schema::create('payment_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('purpose')->index();
            $table->string('status')->default('creating')->index();
            $table->string('provider_transaction_id')->nullable()->unique();
            $table->unsignedBigInteger('base_amount_idr');
            $table->unsignedBigInteger('final_amount_idr')->nullable();
            $table->longText('qr_string')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('review_reason')->nullable();
            $table->timestamps();
        });
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->nullable()->constrained('payment_invoices')->nullOnDelete();
            $table->string('event_hash', 64)->unique();
            $table->string('provider_transaction_id')->nullable();
            $table->string('status');
            $table->unsignedBigInteger('amount_idr')->nullable();
            $table->string('outcome');
            $table->timestamps();
        });
        Schema::create('wallet_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('balance_idr')->default(0);
            $table->timestamps();
        });
        Schema::create('wallet_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_account_id')->constrained()->restrictOnDelete();
            $table->string('kind');
            $table->bigInteger('amount_idr');
            $table->unsignedBigInteger('balance_after_idr');
            $table->string('reference')->unique();
            $table->text('reason')->nullable();
            $table->timestamps();
        });
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('amount_idr');
            $table->string('method');
            $table->string('status')->default('requested');
            $table->text('reason');
            $table->text('evidence')->nullable();
            $table->timestamps();
        });
        Schema::create('content_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->longText('body');
            $table->string('version')->default('1');
            $table->boolean('published')->default(false);
            $table->timestamps();
        });
        Schema::create('settings_public', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['admin_audit_logs','settings_public','content_pages','refunds','wallet_ledger_entries','wallet_accounts','payment_events','payment_invoices','order_items','inventory_units','orders','product_variants','products','categories','social_accounts'] as $name) {
            Schema::dropIfExists($name);
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role','blocked','last_login_at']);
        });
    }
};
