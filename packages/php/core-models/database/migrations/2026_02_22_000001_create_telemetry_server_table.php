<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telemetry_server', function (Blueprint $table): void {
            $table->string('server_id')->primary();
            $table->string('node_id');
            $table->unsignedInteger('players_online')->nullable();
            $table->decimal('cpu_pct', 10, 3);
            $table->decimal('io_write_bytes_per_s', 14, 3);
            $table->timestamps();

            $table->index('node_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telemetry_server');
    }
};
