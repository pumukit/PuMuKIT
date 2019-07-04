<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Event\PermissionProfileEvent;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Services\PermissionProfileEventDispatcherService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 * @coversNothing
 */
class PermissionProfileEventDispatcherServiceTest extends WebTestCase
{
    const EMPTY_NAME = 'EMTPY_NAME';

    private $dm;
    private $permissionProfileDispatcher;
    private $dispatcher;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dm = static::$kernel->getContainer()
            ->get('doctrine_mongodb.odm.document_manager')
        ;
        $this->dispatcher = new EventDispatcher();

        $this->dm->getDocumentCollection(PermissionProfile::class)->remove([]);
        $this->dm->flush();

        MockUpPermissionProfileListener::$called = false;
        MockUpPermissionProfileListener::$name = self::EMPTY_NAME;

        $this->permissionProfileDispatcher = new PermissionProfileEventDispatcherService($this->dispatcher);
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->dispatcher = null;
        $this->permissionProfileDispatcher = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testDispatchCreate()
    {
        $this->dispatcher->addListener(SchemaEvents::PERMISSIONPROFILE_CREATE, function ($event, $name) {
            $this->assertTrue($event instanceof PermissionProfileEvent);
            $this->assertEquals(SchemaEvents::PERMISSIONPROFILE_CREATE, $name);

            $permissionProfile = $event->getPermissionProfile();

            MockUpPermissionProfileListener::$called = true;
            MockUpPermissionProfileListener::$name = $permissionProfile->getName();
        });

        $this->assertFalse(MockUpPermissionProfileListener::$called);
        $this->assertEquals(self::EMPTY_NAME, MockUpPermissionProfileListener::$name);

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
        $this->dispatcher->addListener(SchemaEvents::PERMISSIONPROFILE_UPDATE, function ($event, $name) {
            $this->assertTrue($event instanceof PermissionProfileEvent);
            $this->assertEquals(SchemaEvents::PERMISSIONPROFILE_UPDATE, $name);

            $permissionProfile = $event->getPermissionProfile();

            MockUpPermissionProfileListener::$called = true;
            MockUpPermissionProfileListener::$name = $permissionProfile->getName();
        });

        $this->assertFalse(MockUpPermissionProfileListener::$called);
        $this->assertEquals(self::EMPTY_NAME, MockUpPermissionProfileListener::$name);

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
        $this->dispatcher->addListener(SchemaEvents::PERMISSIONPROFILE_DELETE, function ($event, $name) {
            $this->assertTrue($event instanceof PermissionProfileEvent);
            $this->assertEquals(SchemaEvents::PERMISSIONPROFILE_DELETE, $name);

            $permissionProfile = $event->getPermissionProfile();

            MockUpPermissionProfileListener::$called = true;
            MockUpPermissionProfileListener::$name = $permissionProfile->getName();
        });

        $this->assertFalse(MockUpPermissionProfileListener::$called);
        $this->assertEquals(self::EMPTY_NAME, MockUpPermissionProfileListener::$name);

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
    public static $called = false;
    public static $name = PermissionProfileEventDispatcherServiceTest::EMPTY_NAME;
}
