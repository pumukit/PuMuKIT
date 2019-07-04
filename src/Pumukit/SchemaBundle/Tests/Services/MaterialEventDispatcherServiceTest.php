<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Material;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Event\MaterialEvent;
use Pumukit\SchemaBundle\Services\MaterialEventDispatcherService;

class MaterialEventDispatcherServiceTest extends WebTestCase
{
    const EMPTY_TITLE = 'EMTPY TITLE';
    const EMPTY_URL = 'EMTPY URL';

    private $materialDispatcher;
    private $dispatcher;
    private $linkDispatcher;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dispatcher = static::$kernel->getContainer()
          ->get('event_dispatcher');

        MockUpMaterialListener::$called = false;
        MockUpMaterialListener::$title = self::EMPTY_TITLE;
        MockUpMaterialListener::$url = self::EMPTY_URL;

        $this->materialDispatcher = new MaterialEventDispatcherService($this->dispatcher);
    }

    public function tearDown()
    {
        $this->dispatcher = null;
        $this->linkDispatcher = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testDispatchCreate()
    {
        $this->dispatcher->addListener(SchemaEvents::MATERIAL_CREATE, function ($event, $name) {
            $this->assertTrue($event instanceof MaterialEvent);
            $this->assertEquals(SchemaEvents::MATERIAL_CREATE, $name);

            $multimediaObject = $event->getMultimediaObject();
            $material = $event->getMaterial();

            MockUpMaterialListener::$called = true;
            MockUpMaterialListener::$title = $multimediaObject->getTitle();
            MockUpMaterialListener::$url = $material->getUrl();
        });

        $this->assertFalse(MockUpMaterialListener::$called);
        $this->assertEquals(self::EMPTY_TITLE, MockUpMaterialListener::$title);
        $this->assertEquals(self::EMPTY_URL, MockUpMaterialListener::$url);

        $title = 'test title';
        $url = 'http://testmaterial.com';

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);

        $material = new Material();
        $material->setUrl($url);

        $this->materialDispatcher->dispatchCreate($multimediaObject, $material);

        $this->assertTrue(MockUpMaterialListener::$called);
        $this->assertEquals($title, MockUpMaterialListener::$title);
        $this->assertEquals($url, MockUpMaterialListener::$url);
    }

    public function testDispatchUpdate()
    {
        $this->dispatcher->addListener(SchemaEvents::MATERIAL_UPDATE, function ($event, $name) {
            $this->assertTrue($event instanceof MaterialEvent);
            $this->assertEquals(SchemaEvents::MATERIAL_UPDATE, $name);

            $multimediaObject = $event->getMultimediaObject();
            $material = $event->getMaterial();

            MockUpMaterialListener::$called = true;
            MockUpMaterialListener::$title = $multimediaObject->getTitle();
            MockUpMaterialListener::$url = $material->getUrl();
        });

        $this->assertFalse(MockUpMaterialListener::$called);
        $this->assertEquals(self::EMPTY_TITLE, MockUpMaterialListener::$title);
        $this->assertEquals(self::EMPTY_URL, MockUpMaterialListener::$url);

        $title = 'test title';
        $url = 'http://testmaterial.com';

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);

        $material = new Material();
        $material->setUrl($url);

        $updateUrl = 'http://testmaterialupdate.com';
        $material->setUrl($updateUrl);

        $this->materialDispatcher->dispatchUpdate($multimediaObject, $material);

        $this->assertTrue(MockUpMaterialListener::$called);
        $this->assertEquals($title, MockUpMaterialListener::$title);
        $this->assertEquals($updateUrl, MockUpMaterialListener::$url);
    }

    public function testDispatchDelete()
    {
        $this->dispatcher->addListener(SchemaEvents::MATERIAL_DELETE, function ($event, $name) {
            $this->assertTrue($event instanceof MaterialEvent);
            $this->assertEquals(SchemaEvents::MATERIAL_DELETE, $name);

            $multimediaObject = $event->getMultimediaObject();
            $material = $event->getMaterial();

            MockUpMaterialListener::$called = true;
            MockUpMaterialListener::$title = $multimediaObject->getTitle();
            MockUpMaterialListener::$url = $material->getUrl();
        });

        $this->assertFalse(MockUpMaterialListener::$called);
        $this->assertEquals(self::EMPTY_TITLE, MockUpMaterialListener::$title);
        $this->assertEquals(self::EMPTY_URL, MockUpMaterialListener::$url);

        $title = 'test title';
        $url = 'http://testmaterial.com';

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);

        $material = new Material();
        $material->setUrl($url);

        $this->materialDispatcher->dispatchDelete($multimediaObject, $material);

        $this->assertTrue(MockUpMaterialListener::$called);
        $this->assertEquals($title, MockUpMaterialListener::$title);
        $this->assertEquals($url, MockUpMaterialListener::$url);
    }
}

class MockUpMaterialListener
{
    public static $called = false;
    public static $title = MaterialEventDispatcherServiceTest::EMPTY_TITLE;
    public static $url = MaterialEventDispatcherServiceTest::EMPTY_URL;
}
