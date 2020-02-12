<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Event\MultimediaObjectEvent;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Services\MultimediaObjectEventDispatcherService;

/**
 * @internal
 * @coversNothing
 */
class MultimediaObjectEventDispatcherServiceTest extends PumukitTestCase
{
    const EMPTY_TITLE = 'EMTPY TITLE';

    private $multimediaObjectDispatcher;
    private $dispatcher;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->dispatcher = static::$kernel->getContainer()->get('event_dispatcher');

        MockUpMultimediaObjectListener::$called = false;
        MockUpMultimediaObjectListener::$title = self::EMPTY_TITLE;

        $this->multimediaObjectDispatcher = new MultimediaObjectEventDispatcherService();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dispatcher = null;
        $this->multimediaObjectDispatcher = null;
        gc_collect_cycles();
    }

    public function testDispatchCreate()
    {
        $this->dispatcher->addListener(SchemaEvents::MULTIMEDIAOBJECT_CREATE, function ($event, $name) {
            $this->assertInstanceOf(MultimediaObjectEvent::class, $event);
            $this->assertEquals(SchemaEvents::MULTIMEDIAOBJECT_CREATE, $name);

            $multimediaObject = $event->getMultimediaObject();

            MockUpMultimediaObjectListener::$called = true;
            MockUpMultimediaObjectListener::$title = $multimediaObject->getTitle();
        });

        $this->assertFalse(MockUpMultimediaObjectListener::$called);
        $this->assertEquals(self::EMPTY_TITLE, MockUpMultimediaObjectListener::$title);

        $title = 'test title';

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);

        $this->multimediaObjectDispatcher->dispatchCreate($multimediaObject);

        $this->assertTrue(MockUpMultimediaObjectListener::$called);
        $this->assertEquals($title, MockUpMultimediaObjectListener::$title);
    }

    public function testDispatchUpdate()
    {
        $this->dispatcher->addListener(SchemaEvents::MULTIMEDIAOBJECT_UPDATE, function ($event, $name) {
            $this->assertInstanceOf(MultimediaObjectEvent::class, $event);
            $this->assertEquals(SchemaEvents::MULTIMEDIAOBJECT_UPDATE, $name);

            $multimediaObject = $event->getMultimediaObject();

            MockUpMultimediaObjectListener::$called = true;
            MockUpMultimediaObjectListener::$title = $multimediaObject->getTitle();
        });

        $this->assertFalse(MockUpMultimediaObjectListener::$called);
        $this->assertEquals(self::EMPTY_TITLE, MockUpMultimediaObjectListener::$title);

        $title = 'test title';

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);

        $updateTitle = 'New title';
        $multimediaObject->setTitle($updateTitle);

        $this->multimediaObjectDispatcher->dispatchUpdate($multimediaObject);

        $this->assertTrue(MockUpMultimediaObjectListener::$called);
        $this->assertEquals($updateTitle, MockUpMultimediaObjectListener::$title);
    }

    public function testDispatchDelete()
    {
        $this->dispatcher->addListener(SchemaEvents::MULTIMEDIAOBJECT_DELETE, function ($event, $name) {
            $this->assertInstanceOf(MultimediaObjectEvent::class, $event);
            $this->assertEquals(SchemaEvents::MULTIMEDIAOBJECT_DELETE, $name);

            $multimediaObject = $event->getMultimediaObject();

            MockUpMultimediaObjectListener::$called = true;
            MockUpMultimediaObjectListener::$title = $multimediaObject->getTitle();
        });

        $this->assertFalse(MockUpMultimediaObjectListener::$called);
        $this->assertEquals(self::EMPTY_TITLE, MockUpMultimediaObjectListener::$title);

        $title = 'test title';

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);

        $this->multimediaObjectDispatcher->dispatchDelete($multimediaObject);

        $this->assertTrue(MockUpMultimediaObjectListener::$called);
        $this->assertEquals($title, MockUpMultimediaObjectListener::$title);
    }
}

class MockUpMultimediaObjectListener
{
    public static $called = false;
    public static $title = MultimediaObjectEventDispatcherServiceTest::EMPTY_TITLE;
}
