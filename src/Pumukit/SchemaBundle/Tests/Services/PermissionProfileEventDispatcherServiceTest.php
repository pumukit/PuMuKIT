<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Event\PermissionProfileEvent;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Services\PermissionProfileEventDispatcherService;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 *
 * @coversNothing
 */
class PermissionProfileEventDispatcherServiceTest extends PumukitTestCase
{
    public const EMPTY_NAME = 'EMTPY_NAME';

    private $permissionProfileDispatcher;
    private $dispatcher;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();

        $this->dispatcher = new EventDispatcher();

        MockUpPermissionProfileListener::$called = false;
        MockUpPermissionProfileListener::$name = self::EMPTY_NAME;

        $this->permissionProfileDispatcher = new PermissionProfileEventDispatcherService($this->dispatcher);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->dispatcher = null;
        $this->permissionProfileDispatcher = null;
        gc_collect_cycles();
    }

    public function testDispatchCreate()
    {
        $this->dispatcher->addListener(SchemaEvents::PERMISSIONPROFILE_CREATE, function ($event, $name) {
            static::assertInstanceOf(PermissionProfileEvent::class, $event);
            static::assertEquals(SchemaEvents::PERMISSIONPROFILE_CREATE, $name);

            $permissionProfile = $event->getPermissionProfile();

            MockUpPermissionProfileListener::$called = true;
            MockUpPermissionProfileListener::$name = $permissionProfile->getName();
        });

        static::assertFalse(MockUpPermissionProfileListener::$called);
        static::assertEquals(self::EMPTY_NAME, MockUpPermissionProfileListener::$name);

        $name = 'test_name';

        $permissionProfile = new PermissionProfile();
        $permissionProfile->setName($name);

        $this->dm->persist($permissionProfile);
        $this->dm->flush();

        $this->permissionProfileDispatcher->dispatchCreate($permissionProfile);

        static::assertTrue(MockUpPermissionProfileListener::$called);
        static::assertEquals($name, MockUpPermissionProfileListener::$name);
    }

    public function testDispatchUpdate()
    {
        $this->dispatcher->addListener(SchemaEvents::PERMISSIONPROFILE_UPDATE, function ($event, $name) {
            static::assertInstanceOf(PermissionProfileEvent::class, $event);
            static::assertEquals(SchemaEvents::PERMISSIONPROFILE_UPDATE, $name);

            $permissionProfile = $event->getPermissionProfile();

            MockUpPermissionProfileListener::$called = true;
            MockUpPermissionProfileListener::$name = $permissionProfile->getName();
        });

        static::assertFalse(MockUpPermissionProfileListener::$called);
        static::assertEquals(self::EMPTY_NAME, MockUpPermissionProfileListener::$name);

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

        static::assertTrue(MockUpPermissionProfileListener::$called);
        static::assertEquals($updateName, MockUpPermissionProfileListener::$name);
    }

    public function testDispatchDelete()
    {
        $this->dispatcher->addListener(SchemaEvents::PERMISSIONPROFILE_DELETE, function ($event, $name) {
            static::assertInstanceOf(PermissionProfileEvent::class, $event);
            static::assertEquals(SchemaEvents::PERMISSIONPROFILE_DELETE, $name);

            $permissionProfile = $event->getPermissionProfile();

            MockUpPermissionProfileListener::$called = true;
            MockUpPermissionProfileListener::$name = $permissionProfile->getName();
        });

        static::assertFalse(MockUpPermissionProfileListener::$called);
        static::assertEquals(self::EMPTY_NAME, MockUpPermissionProfileListener::$name);

        $name = 'test_name';

        $permissionProfile = new PermissionProfile();
        $permissionProfile->setName($name);

        $this->dm->persist($permissionProfile);
        $this->dm->flush();

        $this->permissionProfileDispatcher->dispatchDelete($permissionProfile);

        static::assertTrue(MockUpPermissionProfileListener::$called);
        static::assertEquals($name, MockUpPermissionProfileListener::$name);
    }
}

class MockUpPermissionProfileListener
{
    public static $called = false;
    public static $name = PermissionProfileEventDispatcherServiceTest::EMPTY_NAME;
}
