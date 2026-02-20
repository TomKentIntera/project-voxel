<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regional_proxies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('region');
            $table->string('token_hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['region', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regional_proxies');
    }
};

