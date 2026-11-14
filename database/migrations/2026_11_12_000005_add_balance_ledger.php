<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Saldo deposit.
 * - users.balance  : saldo saat ini (cache), tidak boleh negatif.
 * - balance_ledger : riwayat mutasi append-only; akun runtime hanya diberi SELECT, INSERT.
 * - orders.kind    : purchase (beli produk) atau topup (isi saldo lewat QRIS).
 * - orders.payment_method : qris atau balance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('balance', 12, 0)->default(0)->after('role');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->enum('kind', ['purchase', 'topup'])->default('purchase')->after('code');
            $table->enum('payment_method', ['qris', 'balance'])->default('qris')->after('kind');
        });

        Schema::create('balance_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->restrictOnDelete();
            $table->enum('type', ['topup', 'purchase', 'refund', 'adjustment']);
            $table->decimal('amount', 12, 0);        // + masuk, - keluar
            $table->decimal('balance_after', 12, 0); // saldo setelah mutasi ini
            $table->string('note', 255)->nullable();
            $table->dateTime('created_at');
            $table->index(['user_id', 'id']);
            // Satu pesanan hanya bisa menghasilkan satu mutasi per jenis (cegah kredit/debit ganda).
            $table->unique(['order_id', 'type']);
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE users ADD CONSTRAINT chk_users_balance CHECK (balance >= 0)');
            DB::statement('ALTER TABLE balance_ledger ADD CONSTRAINT chk_ledger_amount CHECK (amount <> 0)');
            DB::statement('ALTER TABLE balance_ledger ADD CONSTRAINT chk_ledger_after CHECK (balance_after >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_ledger');

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE users DROP CONSTRAINT chk_users_balance');
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['kind', 'payment_method']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('balance');
        });
    }
};
