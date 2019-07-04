<?php

namespace Pumukit\BasePlayerBundle\Tests\Services;

use PHPUnit\Framework\TestCase;
use Pumukit\BasePlayerBundle\Services\IntroService;

/**
 * @internal
 * @coversNothing
 */
class IntroServiceTest extends TestCase
{
    public static $testIntro = 'https://videos.net/video.mp4';
    public static $testCustomIntro = 'https://videos.net/video_objmm.mp4';

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
        $this->assertFalse($service->getIntroForMultimediaObject());
        $this->assertFalse($service->getIntroForMultimediaObject(null, null));
        $this->assertFalse($service->getIntroForMultimediaObject(null, true));
        $this->assertFalse($service->getIntroForMultimediaObject(null, false));
        $this->assertFalse($service->getIntroForMultimediaObject(false, null));
        $this->assertFalse($service->getIntroForMultimediaObject(false, true));
        $this->assertFalse($service->getIntroForMultimediaObject(false, false));
        $this->assertEquals(static::$testCustomIntro, $service->getIntroForMultimediaObject(static::$testCustomIntro, null));
        $this->assertEquals(static::$testCustomIntro, $service->getIntroForMultimediaObject(static::$testCustomIntro, true));
        $this->assertFalse($service->getIntroForMultimediaObject(static::$testCustomIntro, false));
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
        $this->assertEquals(static::$testIntro, $service->getIntroForMultimediaObject());
        $this->assertEquals(static::$testIntro, $service->getIntroForMultimediaObject(null, null));
        $this->assertEquals(static::$testIntro, $service->getIntroForMultimediaObject(null, true));
        $this->assertFalse($service->getIntroForMultimediaObject(null, false));
        $this->assertFalse($service->getIntroForMultimediaObject(false, null));
        $this->assertFalse($service->getIntroForMultimediaObject(false, true));
        $this->assertFalse($service->getIntroForMultimediaObject(false, false));
        $this->assertEquals(static::$testCustomIntro, $service->getIntroForMultimediaObject(static::$testCustomIntro, null));
        $this->assertEquals(static::$testCustomIntro, $service->getIntroForMultimediaObject(static::$testCustomIntro, true));
        $this->assertFalse($service->getIntroForMultimediaObject(static::$testCustomIntro, false));
    }
}
