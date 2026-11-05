<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('policy_versions',function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->string('version');
            $table->longText('body');
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['slug','version']);
        });
    }
    public function down(): void { Schema::dropIfExists('policy_versions'); }
};
