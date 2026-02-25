<?php

declare(strict_types=1);

namespace Interadigital\CoreModels\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Interadigital\CoreModels\Models\Node;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Interadigital\CoreModels\Models\Node>
 */
class NodeFactory extends Factory
{
    /**
     * @var class-string<\Interadigital\CoreModels\Models\Node>
     */
    protected $model = Node::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rawToken = Node::generateToken();

        return [
            'id' => (string) Str::uuid(),
            'name' => $this->faker->unique()->words(2, true).' node',
            'region' => $this->faker->randomElement(['eu.de', 'us.east', 'us.west', 'ap.southeast']),
            'ip_address' => $this->faker->ipv4(),
            'ptero_node_id' => null,
            'ptero_location_id' => $this->faker->numberBetween(1, 8),
            'fqdn' => $this->faker->domainName(),
            'scheme' => $this->faker->randomElement(['http', 'https']),
            'behind_proxy' => $this->faker->boolean(),
            'maintenance_mode' => false,
            'memory' => $this->faker->numberBetween(8192, 65536),
            'memory_overallocate' => 0,
            'disk' => $this->faker->numberBetween(102400, 512000),
            'disk_overallocate' => 0,
            'upload_size' => $this->faker->numberBetween(100, 1000),
            'daemon_sftp' => 2022,
            'daemon_listen' => 8080,
            'allocation_ip' => $this->faker->ipv4(),
            'allocation_alias' => null,
            'allocation_ports' => ['25565-25580'],
            'sync_status' => Node::SYNC_STATUS_PENDING,
            'sync_error' => null,
            'synced_at' => null,
            'token_hash' => Node::hashToken($rawToken),
            'last_active_at' => null,
            'last_used_at' => null,
        ];
    }
}
