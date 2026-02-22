<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('telemetry_node_sample');
        Schema::dropIfExists('telemetry_server_sample');
    }

    public function down(): void
    {
        Schema::create('telemetry_node_sample', function (Blueprint $table): void {
            $table->id();
            $table->string('node_id');
            $table->decimal('cpu_pct', 10, 3);
            $table->decimal('iowait_pct', 10, 3);
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['node_id', 'recorded_at']);
        });

        Schema::create('telemetry_server_sample', function (Blueprint $table): void {
            $table->id();
            $table->string('server_id');
            $table->string('node_id');
            $table->unsignedInteger('players_online')->nullable();
            $table->decimal('cpu_pct', 10, 3);
            $table->decimal('io_write_bytes_per_s', 14, 3);
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['server_id', 'recorded_at']);
            $table->index(['node_id', 'recorded_at']);
        });
    }
};
