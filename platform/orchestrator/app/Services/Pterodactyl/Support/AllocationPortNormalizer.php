<?php

declare(strict_types=1);

namespace App\Services\Pterodactyl\Support;

use InvalidArgumentException;

final class AllocationPortNormalizer
{
    /**
     * @param  list<mixed>  $rawPorts
     * @return list<string>
     */
    public static function normalizeForStorage(array $rawPorts): array
    {
        $normalized = [];

        foreach ($rawPorts as $rawPort) {
            [$startPort, $endPort] = self::parsePortExpression($rawPort);
            $normalized[] = $startPort === $endPort
                ? (string) $startPort
                : sprintf('%d-%d', $startPort, $endPort);
        }

        $normalized = array_values(array_unique($normalized));

        usort($normalized, static function (string $left, string $right): int {
            [$leftStart, $leftEnd] = self::parsePortExpression($left);
            [$rightStart, $rightEnd] = self::parsePortExpression($right);

            $startComparison = $leftStart <=> $rightStart;

            if ($startComparison !== 0) {
                return $startComparison;
            }

            return $leftEnd <=> $rightEnd;
        });

        return $normalized;
    }

    /**
     * @param  list<mixed>  $rawPorts
     * @return list<int>
     */
    public static function expand(array $rawPorts): array
    {
        $expanded = [];

        foreach ($rawPorts as $rawPort) {
            [$startPort, $endPort] = self::parsePortExpression($rawPort);

            for ($port = $startPort; $port <= $endPort; $port++) {
                $expanded[$port] = true;
            }
        }

        /** @var list<int> $ports */
        $ports = array_map(static fn (string $port): int => (int) $port, array_keys($expanded));
        sort($ports);

        return $ports;
    }

    /**
     * @return array{int, int}
     */
    private static function parsePortExpression(mixed $rawPort): array
    {
        if (is_int($rawPort)) {
            self::assertPortInRange($rawPort);

            return [$rawPort, $rawPort];
        }

        if (! is_string($rawPort)) {
            throw new InvalidArgumentException('Allocation ports must be integers or strings.');
        }

        $expression = trim($rawPort);

        if ($expression === '') {
            throw new InvalidArgumentException('Allocation port expressions cannot be empty.');
        }

        if (preg_match('/^\d{1,5}$/', $expression) === 1) {
            $port = (int) $expression;
            self::assertPortInRange($port);

            return [$port, $port];
        }

        if (preg_match('/^(\d{1,5})-(\d{1,5})$/', $expression, $matches) !== 1) {
            throw new InvalidArgumentException(sprintf(
                'Invalid allocation port expression "%s". Use a single port (25565) or range (25565-25570).',
                $expression,
            ));
        }

        $rangeStart = (int) $matches[1];
        $rangeEnd = (int) $matches[2];

        self::assertPortInRange($rangeStart);
        self::assertPortInRange($rangeEnd);

        if ($rangeEnd < $rangeStart) {
            throw new InvalidArgumentException(sprintf(
                'Invalid allocation port range "%s": range end must be greater than or equal to range start.',
                $expression,
            ));
        }

        return [$rangeStart, $rangeEnd];
    }

    private static function assertPortInRange(int $port): void
    {
        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException(sprintf(
                'Allocation port "%d" is outside the valid TCP port range (1-65535).',
                $port,
            ));
        }
    }
}
