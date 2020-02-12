<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Event\UserEvent;
use Pumukit\SchemaBundle\Services\UserEventDispatcherService;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 * @coversNothing
 */
class UserEventDispatcherServiceTest extends PumukitTestCase
{
    const EMPTY_NAME = 'EMTPY_NAME';

    private $userDispatcher;
    private $dispatcher;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();

        $this->dispatcher = new EventDispatcher();

        MockUpUserListener::$called = false;
        MockUpUserListener::$name = self::EMPTY_NAME;

        $this->userDispatcher = new UserEventDispatcherService();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->dispatcher = null;
        $this->userDispatcher = null;
        gc_collect_cycles();
    }

    public function testDispatchCreate()
    {
        $this->dispatcher->addListener(SchemaEvents::USER_CREATE, function ($event, $name) {
            $this->assertInstanceOf(UserEvent::class, $event);
            $this->assertEquals(SchemaEvents::USER_CREATE, $name);

            $user = $event->getUser();

            MockUpUserListener::$called = true;
            MockUpUserListener::$name = $user->getUsername();
        });

        $this->assertFalse(MockUpUserListener::$called);
        $this->assertEquals(self::EMPTY_NAME, MockUpUserListener::$name);

        $name = 'test_name';

        $user = new User();
        $user->setUsername($name);

        $this->dm->persist($user);
        $this->dm->flush();

        $this->userDispatcher->dispatchCreate($user);

        $this->assertTrue(MockUpUserListener::$called);
        $this->assertEquals($name, MockUpUserListener::$name);
    }

    public function testDispatchUpdate()
    {
        $this->dispatcher->addListener(SchemaEvents::USER_UPDATE, function ($event, $name) {
            $this->assertInstanceOf(UserEvent::class, $event);
            $this->assertEquals(SchemaEvents::USER_UPDATE, $name);

            $user = $event->getUser();

            MockUpUserListener::$called = true;
            MockUpUserListener::$name = $user->getUsername();
        });

        $this->assertFalse(MockUpUserListener::$called);
        $this->assertEquals(self::EMPTY_NAME, MockUpUserListener::$name);

        $name = 'test_name';

        $user = new User();
        $user->setUsername($name);

        $this->dm->persist($user);
        $this->dm->flush();

        $updateUsername = 'New_name';
        $user->setUsername($updateUsername);

        $this->dm->persist($user);
        $this->dm->flush();

        $this->userDispatcher->dispatchUpdate($user);

        $this->assertTrue(MockUpUserListener::$called);
        $this->assertEquals($updateUsername, MockUpUserListener::$name);
    }

    public function testDispatchDelete()
    {
        $this->dispatcher->addListener(SchemaEvents::USER_DELETE, function ($event, $name) {
            $this->assertInstanceOf(UserEvent::class, $event);
            $this->assertEquals(SchemaEvents::USER_DELETE, $name);

            $user = $event->getUser();

            MockUpUserListener::$called = true;
            MockUpUserListener::$name = $user->getUsername();
        });

        $this->assertFalse(MockUpUserListener::$called);
        $this->assertEquals(self::EMPTY_NAME, MockUpUserListener::$name);

        $name = 'test_name';

        $user = new User();
        $user->setUsername($name);

        $this->dm->persist($user);
        $this->dm->flush();

        $this->userDispatcher->dispatchDelete($user);

        $this->assertTrue(MockUpUserListener::$called);
        $this->assertEquals($name, MockUpUserListener::$name);
    }
}

class MockUpUserListener
{
    public static $called = false;
    public static $name = UserEventDispatcherServiceTest::EMPTY_NAME;
}
