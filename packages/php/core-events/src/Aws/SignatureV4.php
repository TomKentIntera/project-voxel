<?php

declare(strict_types=1);

namespace Interadigital\CoreEvents\Aws;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final class SignatureV4
{
    /**
     * @param array<string, string> $headers
     * @param array<string, string> $queryParameters
     * @return array<string, string>
     */
    public static function signRequest(
        string $service,
        string $region,
        string $accessKeyId,
        string $secretAccessKey,
        ?string $sessionToken,
        string $method,
        string $uri,
        array $headers,
        array $queryParameters,
        string $payload,
        ?DateTimeImmutable $now = null,
    ): array {
        self::assertNonEmpty($service, 'service');
        self::assertNonEmpty($region, 'region');
        self::assertNonEmpty($accessKeyId, 'accessKeyId');
        self::assertNonEmpty($secretAccessKey, 'secretAccessKey');
        self::assertNonEmpty($method, 'method');
        self::assertNonEmpty($uri, 'uri');

        $timestamp = ($now ?? new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->setTimezone(new DateTimeZone('UTC'));

        $amzDate = $timestamp->format('Ymd\THis\Z');
        $dateStamp = $timestamp->format('Ymd');

        $parsed = parse_url($uri);
        if ($parsed === false || ! isset($parsed['host'])) {
            throw new InvalidArgumentException(sprintf('Invalid URI [%s] for AWS Signature V4.', $uri));
        }

        $host = $parsed['host'];
        $port = $parsed['port'] ?? null;
        $scheme = $parsed['scheme'] ?? 'https';
        $path = $parsed['path'] ?? '/';
        $canonicalUri = self::canonicalUri($path);
        $canonicalQuery = self::canonicalQueryString($queryParameters);
        $hostHeader = self::hostHeader($host, $port, $scheme);

        $headersToSign = [];
        foreach ($headers as $name => $value) {
            $headersToSign[strtolower(trim($name))] = trim($value);
        }

        $headersToSign['host'] = $hostHeader;
        $headersToSign['x-amz-date'] = $amzDate;

        if (is_string($sessionToken) && trim($sessionToken) !== '') {
            $headersToSign['x-amz-security-token'] = trim($sessionToken);
        }

        $payloadHash = hash('sha256', $payload);
        $headersToSign['x-amz-content-sha256'] = $payloadHash;

        ksort($headersToSign);

        $canonicalHeaders = '';
        $signedHeaderNames = [];
        foreach ($headersToSign as $name => $value) {
            $canonicalHeaders .= $name.':'.self::normalizeHeaderValue($value)."\n";
            $signedHeaderNames[] = $name;
        }

        $signedHeaders = implode(';', $signedHeaderNames);

        $canonicalRequest = strtoupper($method)."\n"
            .$canonicalUri."\n"
            .$canonicalQuery."\n"
            .$canonicalHeaders."\n"
            .$signedHeaders."\n"
            .$payloadHash;

        $credentialScope = $dateStamp.'/'.$region.'/'.$service.'/aws4_request';
        $stringToSign = "AWS4-HMAC-SHA256\n"
            .$amzDate."\n"
            .$credentialScope."\n"
            .hash('sha256', $canonicalRequest);

        $signingKey = self::signingKey($secretAccessKey, $dateStamp, $region, $service);
        $signature = hash_hmac('sha256', $stringToSign, $signingKey);

        $authorizationHeader = 'AWS4-HMAC-SHA256 '
            .'Credential='.$accessKeyId.'/'.$credentialScope.', '
            .'SignedHeaders='.$signedHeaders.', '
            .'Signature='.$signature;

        $resultHeaders = [
            'Authorization' => $authorizationHeader,
            'x-amz-date' => $amzDate,
            'x-amz-content-sha256' => $payloadHash,
            'Host' => $hostHeader,
        ];

        if (isset($headersToSign['x-amz-security-token'])) {
            $resultHeaders['x-amz-security-token'] = $headersToSign['x-amz-security-token'];
        }

        foreach ($headers as $name => $value) {
            $resultHeaders[$name] = $value;
        }

        return $resultHeaders;
    }

    private static function canonicalUri(string $path): string
    {
        if ($path === '') {
            return '/';
        }

        $segments = explode('/', $path);
        $encoded = array_map(
            static fn (string $segment): string => rawurlencode($segment),
            $segments,
        );

        $uri = implode('/', $encoded);

        return str_starts_with($uri, '/') ? $uri : '/'.$uri;
    }

    /**
     * @param array<string, string> $queryParameters
     */
    private static function canonicalQueryString(array $queryParameters): string
    {
        if ($queryParameters === []) {
            return '';
        }

        ksort($queryParameters);

        $parts = [];
        foreach ($queryParameters as $key => $value) {
            $parts[] = rawurlencode((string) $key).'='.rawurlencode((string) $value);
        }

        return implode('&', $parts);
    }

    private static function normalizeHeaderValue(string $value): string
    {
        return preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);
    }

    private static function hostHeader(string $host, ?int $port, string $scheme): string
    {
        if ($port === null) {
            return $host;
        }

        $isDefaultPort = ($scheme === 'https' && $port === 443)
            || ($scheme === 'http' && $port === 80);

        return $isDefaultPort ? $host : $host.':'.$port;
    }

    private static function signingKey(string $secretAccessKey, string $dateStamp, string $region, string $service): string
    {
        $kDate = hash_hmac('sha256', $dateStamp, 'AWS4'.$secretAccessKey, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);

        return hash_hmac('sha256', 'aws4_request', $kService, true);
    }

    private static function assertNonEmpty(string $value, string $field): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException(sprintf('Field [%s] cannot be empty.', $field));
        }
    }
}
