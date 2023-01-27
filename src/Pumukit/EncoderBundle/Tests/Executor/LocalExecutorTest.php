<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Tests\Executor;

use Pumukit\EncoderBundle\Executor\LocalExecutor;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
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
