<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Interadigital\CoreModels\Models\Node;
use Interadigital\CoreModels\Models\User;
use Tests\TestCase;

class NodeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_node_and_receive_one_time_token(): void
    {
        $token = $this->authenticateAdmin();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/nodes', [
                'name' => 'Frankfurt Wings Node',
                'region' => 'eu.de',
                'ip_address' => '203.0.113.10',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Frankfurt Wings Node')
            ->assertJsonPath('data.region', 'eu.de')
            ->assertJsonPath('data.ip_address', '203.0.113.10')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'region',
                    'ip_address',
                    'last_active_at',
                    'last_used_at',
                    'created_at',
                    'updated_at',
                    'node_token',
                ],
            ]);

        $nodeId = (string) $response->json('data.id');
        $rawToken = (string) $response->json('data.node_token');
        $this->assertNotSame('', $rawToken);

        $node = Node::find($nodeId);
        $this->assertNotNull($node);
        $this->assertNotSame($rawToken, $node->token_hash);
        $this->assertTrue($node->matchesToken($rawToken));
    }

    public function test_admin_can_list_nodes_without_exposing_token_hash(): void
    {
        $token = $this->authenticateAdmin();

        Node::factory()->create([
            'id' => 'node-1',
            'name' => 'Node One',
            'region' => 'eu.de',
            'ip_address' => '203.0.113.11',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/nodes?search=Node&per_page=10');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', 'node-1')
            ->assertJsonPath('data.0.name', 'Node One')
            ->assertJsonPath('data.0.region', 'eu.de')
            ->assertJsonPath('data.0.ip_address', '203.0.113.11')
            ->assertJsonMissingPath('data.0.token_hash');
    }

    public function test_show_returns_not_found_for_missing_node(): void
    {
        $token = $this->authenticateAdmin();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/nodes/missing-node')
            ->assertNotFound()
            ->assertJson([
                'message' => 'Node not found.',
            ]);
    }

    private function authenticateAdmin(): string
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'secret1234',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $admin->email,
            'password' => 'secret1234',
        ]);

        $response->assertOk();

        return (string) $response->json('token');
    }
}
