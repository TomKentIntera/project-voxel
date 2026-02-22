<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telemetry_node', function (Blueprint $table): void {
            $table->string('node_id')->primary();
            $table->decimal('cpu_pct', 10, 3);
            $table->decimal('iowait_pct', 10, 3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telemetry_node');
    }
};
