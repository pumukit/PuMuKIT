<?php

namespace Pumukit\EncoderBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\EncoderBundle\Executor\RemoteHTTPExecutor;

class RemoteHTTPExecutorTest extends WebTestCase
{
    public function setUp()
    {
        $options = array('environment' => 'test');
        static::bootKernel($options);
    }

    public function testSimple()
    {
        $this->markTestSkipped('Remote cpu not available in test.');

        $cpu = array(
            'host' => '127.0.0.1:9000',
            'user' => 'pumukit',
            'password' => 'PUMUKIT'
        );

        $executor = new RemoteHTTPExecutor();
        $out = $executor->execute('sleep 1 && echo a', $cpu);
        $this->assertEquals("a\n", "$out");
    }
}
