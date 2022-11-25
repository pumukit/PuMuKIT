<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Utils;

use Predis\Client;

final class RedisUtils
{
    public static function createConnection(): Client
    {
        return new Client((new self())->options());
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

    private function options(): array
    {
        return [
            'scheme' => getenv('REDIS_SCHEME'),
            'host' => getenv('REDIS_HOST'),
            'port' => getenv('REDIS_PORT'),
        ];
    }
}
