<?php

namespace Pumukit\BasePlayerBundle\Tests\Services;

use Pumukit\BasePlayerBundle\Services\IntroService;

class IntroServiceTest extends \PHPUnit_Framework_TestCase
{
    public static $testIntro = 'https://videos.net/video.mp4';

    public function testWithoutIntro()
    {
        $service = new IntroService(null);
        $this->assertFalse($service->getIntro());
        $this->assertFalse($service->getIntro(null));
        $this->assertFalse($service->getIntro(false));
        $this->assertFalse($service->getIntro(true));
        $this->assertFalse($service->getIntro('false'));
        $this->assertFalse($service->getIntro('true'));
        $this->assertFalse($service->getIntro('https://videos.net/stock-footage-suzie-the-bomb-cat-on-the-prowl.mp4'));
    }

    public function testWithIntro()
    {
        $service = new IntroService(static::$testIntro);
        $this->assertEquals(static::$testIntro, $service->getIntro());
        $this->assertEquals(static::$testIntro, $service->getIntro(null));
        $this->assertEquals(static::$testIntro, $service->getIntro(true));
        $this->assertEquals(static::$testIntro, $service->getIntro('true'));
        $this->assertFalse($service->getIntro(false));
        $this->assertFalse($service->getIntro('false'));
        $this->assertFalse($service->getIntro('https://videos.net/stock-footage-suzie-the-bomb-cat-on-the-prowl.mp4'));
    }
}
