<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Event\GroupEvent;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Services\GroupEventDispatcherService;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 * @coversNothing
 */
class GroupEventDispatcherServiceTest extends PumukitTestCase
{
    const EMPTY_NAME = 'EMTPY_NAME';

    private $dispatcher;
    private $groupDispatcher;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->dispatcher = new EventDispatcher();

        MockUpGroupListener::$called = false;
        MockUpGroupListener::$name = self::EMPTY_NAME;

        $this->groupDispatcher = new GroupEventDispatcherService($this->dispatcher);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();
        $this->dm = null;
        $this->dispatcher = null;
        $this->groupDispatcher = null;
        gc_collect_cycles();
    }

    public function testDispatchCreate()
    {
        $this->dispatcher->addListener(SchemaEvents::GROUP_CREATE, function ($event, $name) {
            $this->assertTrue($event instanceof GroupEvent);
            $this->assertEquals(SchemaEvents::GROUP_CREATE, $name);

            $group = $event->getGroup();

            MockUpGroupListener::$called = true;
            MockUpGroupListener::$name = $group->getName();
        });

        $this->assertFalse(MockUpGroupListener::$called);
        $this->assertEquals(self::EMPTY_NAME, MockUpGroupListener::$name);

        $name = 'test_name';

        $group = new Group();
        $group->setName($name);

        $this->dm->persist($group);
        $this->dm->flush();

        $this->groupDispatcher->dispatchCreate($group);

        $this->assertTrue(MockUpGroupListener::$called);
        $this->assertEquals($name, MockUpGroupListener::$name);
    }

    public function testDispatchUpdate()
    {
        $this->dispatcher->addListener(SchemaEvents::GROUP_UPDATE, function ($event, $name) {
            $this->assertTrue($event instanceof GroupEvent);
            $this->assertEquals(SchemaEvents::GROUP_UPDATE, $name);

            $group = $event->getGroup();

            MockUpGroupListener::$called = true;
            MockUpGroupListener::$name = $group->getName();
        });

        $this->assertFalse(MockUpGroupListener::$called);
        $this->assertEquals(self::EMPTY_NAME, MockUpGroupListener::$name);

        $name = 'test_name';

        $group = new Group();
        $group->setName($name);

        $this->dm->persist($group);
        $this->dm->flush();

        $updateName = 'New_name';
        $group->setName($updateName);

        $this->dm->persist($group);
        $this->dm->flush();

        $this->groupDispatcher->dispatchUpdate($group);

        $this->assertTrue(MockUpGroupListener::$called);
        $this->assertEquals($updateName, MockUpGroupListener::$name);
    }

    public function testDispatchDelete()
    {
        $this->dispatcher->addListener(SchemaEvents::GROUP_DELETE, function ($event, $name) {
            $this->assertTrue($event instanceof GroupEvent);
            $this->assertEquals(SchemaEvents::GROUP_DELETE, $name);

            $group = $event->getGroup();

            MockUpGroupListener::$called = true;
            MockUpGroupListener::$name = $group->getName();
        });

        $this->assertFalse(MockUpGroupListener::$called);
        $this->assertEquals(self::EMPTY_NAME, MockUpGroupListener::$name);

        $name = 'test_name';

        $group = new Group();
        $group->setName($name);

        $this->dm->persist($group);
        $this->dm->flush();

        $this->groupDispatcher->dispatchDelete($group);

        $this->assertTrue(MockUpGroupListener::$called);
        $this->assertEquals($name, MockUpGroupListener::$name);
    }
}

class MockUpGroupListener
{
    public static $called = false;
    public static $name = GroupEventDispatcherServiceTest::EMPTY_NAME;
}
