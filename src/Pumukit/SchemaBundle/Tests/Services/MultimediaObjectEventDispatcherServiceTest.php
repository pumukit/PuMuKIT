<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Event\MultimediaObjectEvent;
use Pumukit\SchemaBundle\Services\MultimediaObjectEventDispatcherService;

class MultimediaObjectEventDispatcherServiceTest extends WebTestCase
{
    const EMPTY_TITLE = 'EMTPY TITLE';

    private $multimediaObjectDispatcher;
    private $dispatcher;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dispatcher = static::$kernel->getContainer()
          ->get('event_dispatcher');

        MockUpMultimediaObjectListener::$called = false;
        MockUpMultimediaObjectListener::$title = self::EMPTY_TITLE;

        $this->multimediaObjectDispatcher = new MultimediaObjectEventDispatcherService($this->dispatcher);
    }

    public function tearDown()
    {
        $this->dispatcher = null;
        $this->multimediaObjectDispatcher = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testDispatchCreate()
    {
        $this->dispatcher->addListener(SchemaEvents::MULTIMEDIAOBJECT_CREATE, function ($event, $name) {
            $this->assertTrue($event instanceof MultimediaObjectEvent);
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
            $this->assertTrue($event instanceof MultimediaObjectEvent);
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
            $this->assertTrue($event instanceof MultimediaObjectEvent);
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
