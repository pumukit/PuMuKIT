<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Material;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Event\MaterialEvent;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Services\MaterialEventDispatcherService;

/**
 * @internal
 * @coversNothing
 */
class MaterialEventDispatcherServiceTest extends PumukitTestCase
{
    const EMPTY_TITLE = 'EMTPY TITLE';
    const EMPTY_URL = 'EMTPY URL';

    private $materialDispatcher;
    private $dispatcher;
    private $linkDispatcher;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->dispatcher = static::$kernel->getContainer()->get('event_dispatcher');

        MockUpMaterialListener::$called = false;
        MockUpMaterialListener::$title = self::EMPTY_TITLE;
        MockUpMaterialListener::$url = self::EMPTY_URL;

        $this->materialDispatcher = new MaterialEventDispatcherService($this->dispatcher);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dispatcher = null;
        $this->linkDispatcher = null;
        gc_collect_cycles();
    }

    public function testDispatchCreate()
    {
        $this->dispatcher->addListener(SchemaEvents::MATERIAL_CREATE, function ($event, $name) {
            static::assertInstanceOf(MaterialEvent::class, $event);
            static::assertEquals(SchemaEvents::MATERIAL_CREATE, $name);

            $multimediaObject = $event->getMultimediaObject();
            $material = $event->getMaterial();

            MockUpMaterialListener::$called = true;
            MockUpMaterialListener::$title = $multimediaObject->getTitle();
            MockUpMaterialListener::$url = $material->getUrl();
        });

        static::assertFalse(MockUpMaterialListener::$called);
        static::assertEquals(self::EMPTY_TITLE, MockUpMaterialListener::$title);
        static::assertEquals(self::EMPTY_URL, MockUpMaterialListener::$url);

        $title = 'test title';
        $url = 'http://testmaterial.com';

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);

        $material = new Material();
        $material->setUrl($url);

        $this->materialDispatcher->dispatchCreate($multimediaObject, $material);

        static::assertTrue(MockUpMaterialListener::$called);
        static::assertEquals($title, MockUpMaterialListener::$title);
        static::assertEquals($url, MockUpMaterialListener::$url);
    }

    public function testDispatchUpdate()
    {
        $this->dispatcher->addListener(SchemaEvents::MATERIAL_UPDATE, function ($event, $name) {
            static::assertInstanceOf(MaterialEvent::class, $event);
            static::assertEquals(SchemaEvents::MATERIAL_UPDATE, $name);

            $multimediaObject = $event->getMultimediaObject();
            $material = $event->getMaterial();

            MockUpMaterialListener::$called = true;
            MockUpMaterialListener::$title = $multimediaObject->getTitle();
            MockUpMaterialListener::$url = $material->getUrl();
        });

        static::assertFalse(MockUpMaterialListener::$called);
        static::assertEquals(self::EMPTY_TITLE, MockUpMaterialListener::$title);
        static::assertEquals(self::EMPTY_URL, MockUpMaterialListener::$url);

        $title = 'test title';
        $url = 'http://testmaterial.com';

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);

        $material = new Material();
        $material->setUrl($url);

        $updateUrl = 'http://testmaterialupdate.com';
        $material->setUrl($updateUrl);

        $this->materialDispatcher->dispatchUpdate($multimediaObject, $material);

        static::assertTrue(MockUpMaterialListener::$called);
        static::assertEquals($title, MockUpMaterialListener::$title);
        static::assertEquals($updateUrl, MockUpMaterialListener::$url);
    }

    public function testDispatchDelete()
    {
        $this->dispatcher->addListener(SchemaEvents::MATERIAL_DELETE, function ($event, $name) {
            static::assertInstanceOf(MaterialEvent::class, $event);
            static::assertEquals(SchemaEvents::MATERIAL_DELETE, $name);

            $multimediaObject = $event->getMultimediaObject();
            $material = $event->getMaterial();

            MockUpMaterialListener::$called = true;
            MockUpMaterialListener::$title = $multimediaObject->getTitle();
            MockUpMaterialListener::$url = $material->getUrl();
        });

        static::assertFalse(MockUpMaterialListener::$called);
        static::assertEquals(self::EMPTY_TITLE, MockUpMaterialListener::$title);
        static::assertEquals(self::EMPTY_URL, MockUpMaterialListener::$url);

        $title = 'test title';
        $url = 'http://testmaterial.com';

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);

        $material = new Material();
        $material->setUrl($url);

        $this->materialDispatcher->dispatchDelete($multimediaObject, $material);

        static::assertTrue(MockUpMaterialListener::$called);
        static::assertEquals($title, MockUpMaterialListener::$title);
        static::assertEquals($url, MockUpMaterialListener::$url);
    }
}

class MockUpMaterialListener
{
    public static $called = false;
    public static $title = MaterialEventDispatcherServiceTest::EMPTY_TITLE;
    public static $url = MaterialEventDispatcherServiceTest::EMPTY_URL;
}
