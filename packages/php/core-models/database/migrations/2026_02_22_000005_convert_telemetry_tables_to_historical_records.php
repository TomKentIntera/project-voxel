<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->rebuildTelemetryNodeAsHistorical();
        $this->rebuildTelemetryServerAsHistorical();
    }

    public function down(): void
    {
        $this->rebuildTelemetryNodeAsSnapshot();
        $this->rebuildTelemetryServerAsSnapshot();
    }

    private function rebuildTelemetryNodeAsHistorical(): void
    {
        Schema::create('telemetry_node_historical_tmp', function (Blueprint $table): void {
            $table->id();
            $table->string('node_id');
            $table->decimal('cpu_pct', 10, 3);
            $table->decimal('iowait_pct', 10, 3);
            $table->timestamps();

            $table->index(['node_id', 'created_at']);
        });

        $rows = DB::table('telemetry_node')->get();

        foreach ($rows as $row) {
            DB::table('telemetry_node_historical_tmp')->insert([
                'node_id' => $row->node_id,
                'cpu_pct' => $row->cpu_pct,
                'iowait_pct' => $row->iowait_pct,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        Schema::drop('telemetry_node');
        Schema::rename('telemetry_node_historical_tmp', 'telemetry_node');
    }

    private function rebuildTelemetryServerAsHistorical(): void
    {
        Schema::create('telemetry_server_historical_tmp', function (Blueprint $table): void {
            $table->id();
            $table->string('server_id');
            $table->string('node_id');
            $table->unsignedInteger('players_online')->nullable();
            $table->decimal('cpu_pct', 10, 3);
            $table->decimal('io_write_bytes_per_s', 14, 3);
            $table->timestamps();

            $table->index(['server_id', 'created_at']);
            $table->index(['node_id', 'created_at']);
        });

        $rows = DB::table('telemetry_server')->get();

        foreach ($rows as $row) {
            DB::table('telemetry_server_historical_tmp')->insert([
                'server_id' => $row->server_id,
                'node_id' => $row->node_id,
                'players_online' => $row->players_online,
                'cpu_pct' => $row->cpu_pct,
                'io_write_bytes_per_s' => $row->io_write_bytes_per_s,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        Schema::drop('telemetry_server');
        Schema::rename('telemetry_server_historical_tmp', 'telemetry_server');
    }

    private function rebuildTelemetryNodeAsSnapshot(): void
    {
        Schema::create('telemetry_node_snapshot_tmp', function (Blueprint $table): void {
            $table->string('node_id')->primary();
            $table->decimal('cpu_pct', 10, 3);
            $table->decimal('iowait_pct', 10, 3);
            $table->timestamps();
        });

        $rows = DB::table('telemetry_node')
            ->orderByDesc('created_at')
            ->get();

        $inserted = [];

        foreach ($rows as $row) {
            $nodeId = (string) $row->node_id;

            if (isset($inserted[$nodeId])) {
                continue;
            }

            $inserted[$nodeId] = true;

            DB::table('telemetry_node_snapshot_tmp')->insert([
                'node_id' => $nodeId,
                'cpu_pct' => $row->cpu_pct,
                'iowait_pct' => $row->iowait_pct,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        Schema::drop('telemetry_node');
        Schema::rename('telemetry_node_snapshot_tmp', 'telemetry_node');
    }

    private function rebuildTelemetryServerAsSnapshot(): void
    {
        Schema::create('telemetry_server_snapshot_tmp', function (Blueprint $table): void {
            $table->string('server_id')->primary();
            $table->string('node_id');
            $table->unsignedInteger('players_online')->nullable();
            $table->decimal('cpu_pct', 10, 3);
            $table->decimal('io_write_bytes_per_s', 14, 3);
            $table->timestamps();

            $table->index('node_id');
        });

        $rows = DB::table('telemetry_server')
            ->orderByDesc('created_at')
            ->get();

        $inserted = [];

        foreach ($rows as $row) {
            $serverId = (string) $row->server_id;

            if (isset($inserted[$serverId])) {
                continue;
            }

            $inserted[$serverId] = true;

            DB::table('telemetry_server_snapshot_tmp')->insert([
                'server_id' => $serverId,
                'node_id' => $row->node_id,
                'players_online' => $row->players_online,
                'cpu_pct' => $row->cpu_pct,
                'io_write_bytes_per_s' => $row->io_write_bytes_per_s,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        Schema::drop('telemetry_server');
        Schema::rename('telemetry_server_snapshot_tmp', 'telemetry_server');
    }
};
