<?php

declare(strict_types=1);

namespace App\Metrics;

use Interadigital\CoreModels\Models\Server;

class ServersCount extends Metric
{
    public function key(): string
    {
        return 'servers_count';
    }

    public function label(): string
    {
        return 'Servers';
    }

    public function value(): int
    {
        return Server::count();
    }
}

