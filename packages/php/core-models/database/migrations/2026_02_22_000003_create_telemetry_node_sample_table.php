<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
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
    }

    public function down(): void
    {
        Schema::dropIfExists('telemetry_node_sample');
    }
};
