<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nodes', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('name')->unique();
            $table->string('region');
            $table->string('ip_address', 45);
            $table->string('token_hash', 64)->unique();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['region', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nodes');
    }
};
