<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pumukit\BasePlayerBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

/**
 * @internal
 */
#[CoversClass(Configuration::class)]
final class ConfigurationTest extends TestCase
{
    public function testTheListsAreEmptyByDefault(): void
    {
        $config = $this->process([]);

        self::assertSame([], $config['trackfile_without_token']['series']);
        self::assertSame([], $config['trackfile_without_token']['referer_hosts']);
    }

    public function testHostsAreLowercased(): void
    {
        $config = $this->process(['trackfile_without_token' => [
            'series' => ['5f9d88b9c5e1a2b3c4d5e6f7'],
            'referer_hosts' => ['UNED.es', 'campus.example.org'],
        ]]);

        self::assertSame(['5f9d88b9c5e1a2b3c4d5e6f7'], $config['trackfile_without_token']['series']);
        self::assertSame(['uned.es', 'campus.example.org'], $config['trackfile_without_token']['referer_hosts']);
    }

    #[DataProvider('invalidHosts')]
    public function testAnInvalidHostFails(string $host): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process(['trackfile_without_token' => ['referer_hosts' => [$host]]]);
    }

    public static function invalidHosts(): iterable
    {
        yield 'scheme' => ['https://uned.es'];

        yield 'path' => ['uned.es/x'];

        yield 'port' => ['uned.es:8080'];

        yield 'single label' => ['localhost'];

        yield 'empty' => [''];
    }

    #[DataProvider('invalidSeries')]
    public function testAnInvalidSeriesIdFails(string $id): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process(['trackfile_without_token' => ['series' => [$id]]]);
    }

    public static function invalidSeries(): iterable
    {
        yield 'short' => ['5f9d88b9'];

        yield 'not hexadecimal' => ['zzzzzzzzzzzzzzzzzzzzzzzz'];

        yield 'uppercase' => ['5F9D88B9C5E1A2B3C4D5E6F7'];

        yield 'trailing newline' => ["5f9d88b9c5e1a2b3c4d5e6f7\n"];
    }

    private function process(array $config): array
    {
        return (new Processor())->processConfiguration(new Configuration(), [$config]);
    }
}
