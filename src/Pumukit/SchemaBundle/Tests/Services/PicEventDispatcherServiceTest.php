<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Event\PicEvent;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Services\PicEventDispatcherService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
class PicEventDispatcherServiceTest extends WebTestCase
{
    const EMPTY_TITLE = 'EMTPY TITLE';
    const EMPTY_URL = 'EMTPY URL';

    private $picDispatcher;
    private $dispatcher;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dispatcher = static::$kernel->getContainer()->get('event_dispatcher');

        MockUpPicListener::$called = false;
        MockUpPicListener::$title = self::EMPTY_TITLE;
        MockUpPicListener::$url = self::EMPTY_URL;

        $this->picDispatcher = new PicEventDispatcherService($this->dispatcher);
    }

    public function tearDown(): void
    {
        $this->dispatcher = null;
        $this->picDispatcher = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testDispatchCreate()
    {
        $this->dispatcher->addListener(SchemaEvents::PIC_CREATE, function ($event, $name) {
            static::assertInstanceOf(PicEvent::class, $event);
            static::assertEquals(SchemaEvents::PIC_CREATE, $name);

            $multimediaObject = $event->getMultimediaObject();
            $pic = $event->getPic();

            MockUpPicListener::$called = true;
            MockUpPicListener::$title = $multimediaObject->getTitle();
            MockUpPicListener::$url = $pic->getUrl();
        });

        static::assertFalse(MockUpPicListener::$called);
        static::assertEquals(self::EMPTY_TITLE, MockUpPicListener::$title);
        static::assertEquals(self::EMPTY_URL, MockUpPicListener::$url);

        $title = 'test title';
        $url = 'http://testpic.com';

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);

        $pic = new Pic();
        $pic->setUrl($url);

        $this->picDispatcher->dispatchCreate($multimediaObject, $pic);

        static::assertTrue(MockUpPicListener::$called);
        static::assertEquals($title, MockUpPicListener::$title);
        static::assertEquals($url, MockUpPicListener::$url);
    }

    public function testDispatchUpdate()
    {
        $this->dispatcher->addListener(SchemaEvents::PIC_UPDATE, function ($event, $name) {
            static::assertInstanceOf(PicEvent::class, $event);
            static::assertEquals(SchemaEvents::PIC_UPDATE, $name);

            $multimediaObject = $event->getMultimediaObject();
            $pic = $event->getPic();

            MockUpPicListener::$called = true;
            MockUpPicListener::$title = $multimediaObject->getTitle();
            MockUpPicListener::$url = $pic->getUrl();
        });

        static::assertFalse(MockUpPicListener::$called);
        static::assertEquals(self::EMPTY_TITLE, MockUpPicListener::$title);
        static::assertEquals(self::EMPTY_URL, MockUpPicListener::$url);

        $title = 'test title';
        $url = 'http://testpic.com';

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);

        $pic = new Pic();
        $pic->setUrl($url);

        $updateUrl = 'http://testpicupdate.com';
        $pic->setUrl($updateUrl);

        $this->picDispatcher->dispatchUpdate($multimediaObject, $pic);

        static::assertTrue(MockUpPicListener::$called);
        static::assertEquals($title, MockUpPicListener::$title);
        static::assertEquals($updateUrl, MockUpPicListener::$url);
    }

    public function testDispatchDelete()
    {
        $this->dispatcher->addListener(SchemaEvents::PIC_DELETE, function ($event, $name) {
            static::assertInstanceOf(PicEvent::class, $event);
            static::assertEquals(SchemaEvents::PIC_DELETE, $name);

            $multimediaObject = $event->getMultimediaObject();
            $pic = $event->getPic();

            MockUpPicListener::$called = true;
            MockUpPicListener::$title = $multimediaObject->getTitle();
            MockUpPicListener::$url = $pic->getUrl();
        });

        static::assertFalse(MockUpPicListener::$called);
        static::assertEquals(self::EMPTY_TITLE, MockUpPicListener::$title);
        static::assertEquals(self::EMPTY_URL, MockUpPicListener::$url);

        $title = 'test title';
        $url = 'http://testpic.com';

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);

        $pic = new Pic();
        $pic->setUrl($url);

        $this->picDispatcher->dispatchDelete($multimediaObject, $pic);

        static::assertTrue(MockUpPicListener::$called);
        static::assertEquals($title, MockUpPicListener::$title);
        static::assertEquals($url, MockUpPicListener::$url);
    }
}

class MockUpPicListener
{
    public static $called = false;
    public static $title = PicEventDispatcherServiceTest::EMPTY_TITLE;
    public static $url = PicEventDispatcherServiceTest::EMPTY_URL;
}
