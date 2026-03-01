<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\Http;
use Interadigital\CoreModels\Models\Node;
use Interadigital\CoreModels\Models\User;
use Tests\TestCase;

class NodeProvisioningApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_issue_one_time_node_bootstrap_command_and_download_script_once(): void
    {
        config()->set('services.pterodactyl', [
            'base_url' => 'https://panel.example.test',
            'application_api_key' => 'test-app-api-key',
            'client_api_key' => 'test-client-api-key',
            'timeout' => 15,
        ]);
        config()->set('services.provisioning.monitor_script_url', 'https://downloads.example.test/monitor.py');

        Http::fake([
            'https://panel.example.test/api/application/nodes/321/configuration' => Http::response([
                'debug' => false,
                'uuid' => 'f2b6f48d-1449-4f7a-96d5-69ddbe6eac8c',
                'token_id' => 'Yt7fFgg8lbbYQpTI',
                'token' => 'E6oxWHv0MJUpRpo4guFtiW5CJnBR6anpUWpQlDMFvgIij5OfypiBwfLNcncKopRY',
                'api' => [
                    'host' => '0.0.0.0',
                    'port' => 8080,
                    'ssl' => [
                        'enabled' => false,
                        'cert' => '/etc/letsencrypt/live/example.test/fullchain.pem',
                        'key' => '/etc/letsencrypt/live/example.test/privkey.pem',
                    ],
                    'upload_limit' => 100,
                ],
                'system' => [
                    'data' => '/var/lib/pterodactyl/volumes',
                    'sftp' => [
                        'bind_port' => 2022,
                    ],
                ],
                'allowed_mounts' => [],
                'remote' => 'https://panel.example.test',
            ], 200),
        ]);

        $token = $this->authenticateAdmin();

        $node = Node::factory()->create([
            'id' => 'node-provision-1',
            'ip_address' => '203.0.113.20',
            'ptero_node_id' => 321,
            'sync_status' => Node::SYNC_STATUS_SYNCED,
        ]);
        $originalTokenHash = $node->token_hash;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/nodes/'.$node->id.'/provisioning-command', [
                'ttl_minutes' => 10,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.node_id', $node->id)
            ->assertJsonStructure([
                'data' => [
                    'node_id',
                    'expires_at',
                    'bootstrap_url',
                    'command',
                ],
            ]);

        $command = (string) $response->json('data.command');
        $bootstrapUrl = (string) $response->json('data.bootstrap_url');
        $this->assertStringContainsString('curl -fsSL', $command);
        $this->assertStringContainsString('| sudo bash', $command);
        $this->assertSame(sprintf("curl -fsSL '%s' | sudo bash", $bootstrapUrl), $command);

        $node->refresh();
        $this->assertNotSame($originalTokenHash, $node->token_hash);

        $bootstrapPath = parse_url($bootstrapUrl, PHP_URL_PATH);
        $this->assertIsString($bootstrapPath);
        $this->assertNotSame('', $bootstrapPath);

        $scriptResponse = $this->get((string) $bootstrapPath);
        $scriptResponse->assertOk();
        $scriptResponse->assertHeader('Content-Type', 'text/x-shellscript; charset=utf-8');
        $script = (string) $scriptResponse->getContent();
        $this->assertStringContainsString("NODE_ID='node-provision-1'", $script);
        $this->assertStringContainsString('orchestrator-node-monitor.service', $script);
        $this->assertStringContainsString('wings.service', $script);

        $this->get((string) $bootstrapPath)
            ->assertNotFound()
            ->assertSeeText('expired');

        Http::assertSent(static function (HttpClientRequest $request): bool {
            return $request->method() === 'GET'
                && $request->url() === 'https://panel.example.test/api/application/nodes/321/configuration';
        });
    }

    public function test_issue_command_fails_when_panel_api_configuration_is_missing(): void
    {
        config()->set('services.pterodactyl', [
            'base_url' => '',
            'application_api_key' => '',
            'client_api_key' => '',
            'timeout' => 15,
        ]);

        $token = $this->authenticateAdmin();
        $node = Node::factory()->create([
            'id' => 'node-provision-missing-config',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/nodes/'.$node->id.'/provisioning-command')
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Pterodactyl base URL and application API key must be configured before generating provisioning commands.'
            );
    }

    public function test_bootstrap_script_supports_monitor_archive_download_from_url(): void
    {
        config()->set('services.pterodactyl', [
            'base_url' => 'https://panel.example.test',
            'application_api_key' => 'test-app-api-key',
            'client_api_key' => 'test-client-api-key',
            'timeout' => 15,
        ]);
        config()->set(
            'services.provisioning.monitor_archive_url',
            'https://artifacts.example.test/node-monitor.zip?X-Amz-Signature=demo'
        );
        config()->set(
            'services.provisioning.monitor_archive_sha256',
            'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'
        );
        config()->set('services.provisioning.monitor_archive_entrypoint', 'dist/main.py');

        Http::fake([
            'https://panel.example.test/api/application/nodes/654/configuration' => Http::response([
                'debug' => false,
                'uuid' => 'f2b6f48d-1449-4f7a-96d5-69ddbe6eac8c',
                'token_id' => 'Yt7fFgg8lbbYQpTI',
                'token' => 'E6oxWHv0MJUpRpo4guFtiW5CJnBR6anpUWpQlDMFvgIij5OfypiBwfLNcncKopRY',
                'api' => [
                    'host' => '0.0.0.0',
                    'port' => 8080,
                    'ssl' => [
                        'enabled' => false,
                        'cert' => '/etc/letsencrypt/live/example.test/fullchain.pem',
                        'key' => '/etc/letsencrypt/live/example.test/privkey.pem',
                    ],
                    'upload_limit' => 100,
                ],
                'system' => [
                    'data' => '/var/lib/pterodactyl/volumes',
                    'sftp' => [
                        'bind_port' => 2022,
                    ],
                ],
                'allowed_mounts' => [],
                'remote' => 'https://panel.example.test',
            ], 200),
        ]);

        $token = $this->authenticateAdmin();
        $node = Node::factory()->create([
            'id' => 'node-provision-archive',
            'ip_address' => '203.0.113.25',
            'ptero_node_id' => 654,
            'sync_status' => Node::SYNC_STATUS_SYNCED,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/nodes/'.$node->id.'/provisioning-command');

        $response->assertCreated();
        $bootstrapPath = parse_url((string) $response->json('data.bootstrap_url'), PHP_URL_PATH);

        $this->assertIsString($bootstrapPath);
        $this->assertNotSame('', $bootstrapPath);

        $script = (string) $this->get((string) $bootstrapPath)->getContent();

        $this->assertStringContainsString('Downloading orchestrator monitor archive', $script);
        $this->assertStringContainsString('apt-get install -y unzip', $script);
        $this->assertStringContainsString(
            "curl -fsSL 'https://artifacts.example.test/node-monitor.zip?X-Amz-Signature=demo' -o /tmp/orchestrator-monitor.zip",
            $script
        );
        $this->assertStringContainsString('sha256sum -c -', $script);
        $this->assertStringContainsString(
            "/opt/intera/orchestrator-monitor/.artifact/'dist/main.py'",
            $script
        );
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

