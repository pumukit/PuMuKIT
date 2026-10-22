<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Tests\Executor;

use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\EncoderBundle\Executor\LocalExecutor;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversNothing]
class LocalExecutorTest extends WebTestCase
{
    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
    }

    public function testSimple(): void
    {
        $executor = new LocalExecutor();
        $command = [
            'echo',
            'a',
        ];
        $out = $executor->execute($command);
        static::assertEquals("a\n\n", $out);
    }
}
