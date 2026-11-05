<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Utils;

use Predis\Client;
use TusPhp\Cache\RedisStore;

final class RedisUtils
{
    public static function createConnection(): Client
    {
        return new Client(self::connectionOptions());
    }

    public static function createTusStore(): RedisStore
    {
        return new RedisStore(self::connectionOptions());
    }

    public static function connectionOptions(): array
    {
        return array_filter([
            'scheme' => self::variable('REDIS_SCHEME') ?: 'tcp',
            'host' => self::variable('REDIS_HOST') ?: '127.0.0.1',
            'port' => self::variable('REDIS_PORT') ?: '6379',
            'database' => self::variable('REDIS_DB') ?: null,
            'username' => self::variable('REDIS_USER') ?: null,
            'password' => self::variable('REDIS_PASSWORD') ?: null,
        ], static fn (?string $value): bool => null !== $value);
    }

    public static function elementsByKeyPattern(string $pattern): ?array
    {
        return self::createConnection()->keys($pattern);
    }

    public static function valuesFromKey(string $key): array
    {
        $result = (new self())->findValuesFromKey($key);

        return json_decode($result, true, 512, JSON_THROW_ON_ERROR);
    }

    public static function removeKey(string $key): int
    {
        return self::createConnection()->del($key);
    }

    private function findValuesFromKey(string $key): ?string
    {
        return self::createConnection()->get($key);
    }

    private static function variable(string $name): string
    {
        $value = $_SERVER[$name] ?? $_ENV[$name] ?? getenv($name);

        return is_string($value) ? $value : '';
    }
}
