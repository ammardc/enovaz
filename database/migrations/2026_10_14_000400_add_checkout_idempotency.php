<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('idempotency_key')->nullable();
            $table->unique(['user_id','idempotency_key']);
        });
    }
    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropUnique(['user_id','idempotency_key']));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('idempotency_key'));
    }
};
