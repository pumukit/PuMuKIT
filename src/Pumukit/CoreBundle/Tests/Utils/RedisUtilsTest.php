<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\Utils;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Pumukit\CoreBundle\Utils\RedisUtils;

/**
 * @internal
 */
#[CoversNothing]
class RedisUtilsTest extends TestCase
{
    private const VARIABLES = ['REDIS_SCHEME', 'REDIS_HOST', 'REDIS_PORT', 'REDIS_DB', 'REDIS_USER', 'REDIS_PASSWORD'];

    private array $previous = [];

    protected function setUp(): void
    {
        foreach (self::VARIABLES as $name) {
            $this->previous[$name] = [$_SERVER[$name] ?? null, $_ENV[$name] ?? null, getenv($name)];
            unset($_SERVER[$name], $_ENV[$name]);
            putenv($name);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->previous as $name => [$server, $env, $process]) {
            unset($_SERVER[$name], $_ENV[$name]);
            if (null !== $server) {
                $_SERVER[$name] = $server;
            }
            if (null !== $env) {
                $_ENV[$name] = $env;
            }
            putenv(false === $process ? $name : $name.'='.$process);
        }
    }

    public function testAnInstallationWithoutVariablesUsesTheDefaults(): void
    {
        $this->assertSame(['scheme' => 'tcp', 'host' => '127.0.0.1', 'port' => '6379'], RedisUtils::connectionOptions());
    }

    public function testEmptyCredentialsAreNotSent(): void
    {
        $_SERVER['REDIS_USER'] = '';
        $_SERVER['REDIS_PASSWORD'] = '';

        $this->assertArrayNotHasKey('username', RedisUtils::connectionOptions());
        $this->assertArrayNotHasKey('password', RedisUtils::connectionOptions());
    }

    public function testTheCredentialsAreUsed(): void
    {
        $_SERVER['REDIS_HOST'] = 'redis';
        $_SERVER['REDIS_USER'] = 'app';
        $_SERVER['REDIS_PASSWORD'] = 'secret';

        $options = RedisUtils::connectionOptions();

        $this->assertSame('redis', $options['host']);
        $this->assertSame('app', $options['username']);
        $this->assertSame('secret', $options['password']);
    }
}
