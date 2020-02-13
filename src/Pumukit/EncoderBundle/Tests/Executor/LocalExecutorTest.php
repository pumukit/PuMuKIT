<?php

namespace Pumukit\EncoderBundle\Tests\Executor;

use Pumukit\EncoderBundle\Executor\LocalExecutor;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
class LocalExecutorTest extends WebTestCase
{
    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
    }

    public function testSimple()
    {
        $executor = new LocalExecutor();
        $out = $executor->execute('sleep 1 && echo a');
        static::assertEquals("a\n\n", "{$out}");
    }
}
