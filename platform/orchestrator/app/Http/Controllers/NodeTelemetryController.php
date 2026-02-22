<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Interadigital\CoreModels\Models\TelemetryNode;
use Interadigital\CoreModels\Models\TelemetryServer;
use Symfony\Component\HttpFoundation\Response;

class NodeTelemetryController extends Controller
{
    public function store(Request $request, string $node_id): JsonResponse
    {
        $validated = $request->validate([
            'node_id' => ['required', 'string', 'max:255'],
            'timestamp' => ['nullable', 'date'],
            'node' => ['required', 'array'],
            'node.cpu_pct' => ['required', 'numeric'],
            'node.iowait_pct' => ['required', 'numeric'],
            'servers' => ['required', 'array'],
            'servers.*.server_id' => ['required', 'string', 'max:255'],
            'servers.*.players_online' => ['nullable', 'integer', 'min:0'],
            'servers.*.cpu_pct' => ['required', 'numeric'],
            'servers.*.io_write_bytes_per_s' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validated['node_id'] !== $node_id) {
            return response()->json([
                'message' => 'Payload node_id does not match route node_id.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $telemetryTimestamp = isset($validated['timestamp'])
            ? Carbon::parse((string) $validated['timestamp'])
            : now();

        TelemetryNode::query()->upsert([
            [
                'node_id' => $node_id,
                'cpu_pct' => (float) $validated['node']['cpu_pct'],
                'iowait_pct' => (float) $validated['node']['iowait_pct'],
                'created_at' => $telemetryTimestamp,
                'updated_at' => $telemetryTimestamp,
            ],
        ], ['node_id'], ['cpu_pct', 'iowait_pct', 'updated_at']);

        $serversById = [];

        foreach ($validated['servers'] as $serverTelemetry) {
            $serverId = (string) $serverTelemetry['server_id'];

            $serversById[$serverId] = [
                'server_id' => $serverId,
                'node_id' => $node_id,
                'players_online' => $serverTelemetry['players_online'] ?? null,
                'cpu_pct' => (float) $serverTelemetry['cpu_pct'],
                'io_write_bytes_per_s' => (float) $serverTelemetry['io_write_bytes_per_s'],
                'created_at' => $telemetryTimestamp,
                'updated_at' => $telemetryTimestamp,
            ];
        }

        if ($serversById !== []) {
            TelemetryServer::query()->upsert(
                array_values($serversById),
                ['server_id'],
                ['node_id', 'players_online', 'cpu_pct', 'io_write_bytes_per_s', 'updated_at']
            );
        }

        return response()->json([
            'message' => 'Telemetry accepted.',
        ], Response::HTTP_ACCEPTED);
    }
}
