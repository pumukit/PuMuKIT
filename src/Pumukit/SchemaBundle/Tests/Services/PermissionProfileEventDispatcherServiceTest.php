<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Event\PermissionProfileEvent;
use Pumukit\SchemaBundle\Services\PermissionProfileEventDispatcherService;

class PermissionProfileEventDispatcherServiceTest extends WebTestCase
{
    const EMPTY_NAME = 'EMTPY_NAME';

    private $dm;
    private $permissionProfileDispatcher;

    public function __construct()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();

        $this->dm = $kernel->getContainer()
          ->get('doctrine_mongodb.odm.document_manager');
        $this->dispatcher = $kernel->getContainer()
          ->get('event_dispatcher');
    }

    public function setUp()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:PermissionProfile')->remove(array());
        $this->dm->flush();

        MockUpPermissionProfileListener::$called = false;
        MockUpPermissionProfileListener::$name = PermissionProfileEventDispatcherServiceTest::EMPTY_NAME;

        $this->permissionProfileDispatcher = new PermissionProfileEventDispatcherService($this->dispatcher);
    }

    public function testDispatchCreate()
    {
        $this->dispatcher->addListener(SchemaEvents::PERMISSIONPROFILE_CREATE, function($event, $name)
                                       {
                                           $this->assertTrue($event instanceof PermissionProfileEvent);
                                           $this->assertEquals(SchemaEvents::PERMISSIONPROFILE_CREATE, $name);

                                           $permissionProfile = $event->getPermissionProfile();

                                           MockUpPermissionProfileListener::$called = true;
                                           MockUpPermissionProfileListener::$name = $permissionProfile->getName();
                                       });

        $this->assertFalse(MockUpPermissionProfileListener::$called);
        $this->assertEquals(PermissionProfileEventDispatcherServiceTest::EMPTY_NAME, MockUpPermissionProfileListener::$name);

        $name = 'test_name';

        $permissionProfile = new PermissionProfile();
        $permissionProfile->setName($name);

        $this->dm->persist($permissionProfile);
        $this->dm->flush();

        $this->permissionProfileDispatcher->dispatchCreate($permissionProfile);

        $this->assertTrue(MockUpPermissionProfileListener::$called);
        $this->assertEquals($name, MockUpPermissionProfileListener::$name);
    }

    public function testDispatchUpdate()
    {
        $this->dispatcher->addListener(SchemaEvents::PERMISSIONPROFILE_UPDATE, function($event, $name)
                                       {
                                           $this->assertTrue($event instanceof PermissionProfileEvent);
                                           $this->assertEquals(SchemaEvents::PERMISSIONPROFILE_UPDATE, $name);

                                           $permissionProfile = $event->getPermissionProfile();

                                           MockUpPermissionProfileListener::$called = true;
                                           MockUpPermissionProfileListener::$name = $permissionProfile->getName();
                                       });

        $this->assertFalse(MockUpPermissionProfileListener::$called);
        $this->assertEquals(PermissionProfileEventDispatcherServiceTest::EMPTY_NAME, MockUpPermissionProfileListener::$name);

        $name = 'test_name';

        $permissionProfile = new PermissionProfile();
        $permissionProfile->setName($name);

        $this->dm->persist($permissionProfile);
        $this->dm->flush();

        $updateName = 'New_name';
        $permissionProfile->setName($updateName);

        $this->dm->persist($permissionProfile);
        $this->dm->flush();

        $this->permissionProfileDispatcher->dispatchUpdate($permissionProfile);

        $this->assertTrue(MockUpPermissionProfileListener::$called);
        $this->assertEquals($updateName, MockUpPermissionProfileListener::$name);
    }

    public function testDispatchDelete()
    {
        $this->dispatcher->addListener(SchemaEvents::PERMISSIONPROFILE_DELETE, function($event, $name)
                                       {
                                           $this->assertTrue($event instanceof PermissionProfileEvent);
                                           $this->assertEquals(SchemaEvents::PERMISSIONPROFILE_DELETE, $name);

                                           $permissionProfile = $event->getPermissionProfile();

                                           MockUpPermissionProfileListener::$called = true;
                                           MockUpPermissionProfileListener::$name = $permissionProfile->getName();
                                       });

        $this->assertFalse(MockUpPermissionProfileListener::$called);
        $this->assertEquals(PermissionProfileEventDispatcherServiceTest::EMPTY_NAME, MockUpPermissionProfileListener::$name);

        $name = 'test_name';

        $permissionProfile = new PermissionProfile();
        $permissionProfile->setName($name);

        $this->dm->persist($permissionProfile);
        $this->dm->flush();

        $this->permissionProfileDispatcher->dispatchDelete($permissionProfile);

        $this->assertTrue(MockUpPermissionProfileListener::$called);
        $this->assertEquals($name, MockUpPermissionProfileListener::$name);
    }
}

class MockUpPermissionProfileListener
{
    static public $called = false;
    static public $name = PermissionProfileEventDispatcherServiceTest::EMPTY_NAME;
}