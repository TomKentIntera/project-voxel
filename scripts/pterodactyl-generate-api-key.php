<?php
require '/app/vendor/autoload.php';

$app = require '/app/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$adminEmail = trim((string) getenv('PTERODACTYL_PROVISION_ADMIN_EMAIL'));
if ($adminEmail === '') {
    fwrite(STDERR, 'Missing panel admin email for API key generation.' . PHP_EOL);
    exit(1);
}

$user = Pterodactyl\Models\User::query()->where('email', $adminEmail)->first();
if ($user === null) {
    fwrite(STDERR, sprintf('Could not find panel admin user [%s] for API key generation.', $adminEmail) . PHP_EOL);
    exit(1);
}

$memo = 'Local orchestrator provisioning key';
$key = Pterodactyl\Models\ApiKey::query()
    ->where('user_id', $user->id)
    ->where('key_type', Pterodactyl\Models\ApiKey::TYPE_APPLICATION)
    ->where('memo', $memo)
    ->latest('id')
    ->first();

if ($key === null) {
    $permissions = [
        'r_servers' => 3,
        'r_nodes' => 3,
        'r_allocations' => 3,
        'r_users' => 3,
        'r_locations' => 3,
        'r_nests' => 3,
        'r_eggs' => 3,
        'r_database_hosts' => 3,
        'r_server_databases' => 3,
    ];

    $key = $app->make(Pterodactyl\Services\Api\KeyCreationService::class)
        ->setKeyType(Pterodactyl\Models\ApiKey::TYPE_APPLICATION)
        ->handle([
            'memo' => $memo,
            'user_id' => $user->id,
            'allowed_ips' => [],
        ], $permissions);
}

$token = $key->identifier . $app->make(Illuminate\Contracts\Encryption\Encrypter::class)->decrypt($key->token);
fwrite(STDOUT, $token);

