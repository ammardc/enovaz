<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 2FA (TOTP) untuk admin: rahasia terenkripsi + langkah terakhir yang dipakai (cegah replay kode). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('totp_secret_enc')->nullable()->after('balance');
            $table->dateTime('totp_enabled_at')->nullable()->after('totp_secret_enc');
            $table->unsignedBigInteger('totp_last_step')->nullable()->after('totp_enabled_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['totp_secret_enc', 'totp_enabled_at', 'totp_last_step']);
        });
    }
};
