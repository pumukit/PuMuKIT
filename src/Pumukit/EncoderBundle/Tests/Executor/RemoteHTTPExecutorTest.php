<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Tests\Executor;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pumukit\EncoderBundle\Executor\RemoteHTTPExecutor;

/**
 * @internal
 */
#[CoversNothing]
class RemoteHTTPExecutorTest extends TestCase
{
    #[DataProvider('hosts')]
    public function testTheUrlKeepsTheSchemeOfTheHost(string $host, string $expected): void
    {
        $this->assertSame($expected, (new RemoteHTTPExecutor())->url($host));
    }

    public static function hosts(): iterable
    {
        yield 'only the host keeps working as before' => ['encoder.example.org', 'http://encoder.example.org/webserver.php'];

        yield 'https' => ['https://encoder.example.org', 'https://encoder.example.org/webserver.php'];

        yield 'trailing slash' => ['https://encoder.example.org/', 'https://encoder.example.org/webserver.php'];

        yield 'port' => ['10.0.0.1:8080', 'http://10.0.0.1:8080/webserver.php'];
    }
}
