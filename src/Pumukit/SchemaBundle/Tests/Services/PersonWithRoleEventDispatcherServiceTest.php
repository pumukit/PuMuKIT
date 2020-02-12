<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Event\PersonWithRoleEvent;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Services\PersonWithRoleEventDispatcherService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 * @coversNothing
 */
class PersonWithRoleEventDispatcherServiceTest extends WebTestCase
{
    const EMPTY_TITLE = 'EMTPY TITLE';
    const EMPTY_NAME = 'EMTPY NAME';
    const EMPTY_CODE = 'EMTPY CODE';

    private $personWithRoleDispatcher;
    private $dispatcher;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);

        $this->dispatcher = static::$kernel->getContainer()->get('event_dispatcher');

        MockUpPersonWithRoleListener::$called = false;
        MockUpPersonWithRoleListener::$title = self::EMPTY_TITLE;
        MockUpPersonWithRoleListener::$name = self::EMPTY_NAME;
        MockUpPersonWithRoleListener::$code = self::EMPTY_CODE;

        $this->personWithRoleDispatcher = new PersonWithRoleEventDispatcherService($this->dispatcher);
    }

    public function tearDown(): void
    {
        $this->dispatcher = null;
        $this->personWithRoleDispatcher = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testDispatchCreate()
    {
        $this->dispatcher->addListener(SchemaEvents::PERSONWITHROLE_CREATE, function ($event, $name) {
            $this->assertInstanceOf(PersonWithRoleEvent::class, $event);
            $this->assertEquals(SchemaEvents::PERSONWITHROLE_CREATE, $name);

            $multimediaObject = $event->getMultimediaObject();
            $person = $event->getPerson();
            $role = $event->getRole();

            MockUpPersonWithRoleListener::$called = true;
            MockUpPersonWithRoleListener::$title = $multimediaObject->getTitle();
            MockUpPersonWithRoleListener::$name = $person->getName();
            MockUpPersonWithRoleListener::$code = $role->getCod();
        });

        $this->assertFalse(MockUpPersonWithRoleListener::$called);
        $this->assertEquals(self::EMPTY_TITLE, MockUpPersonWithRoleListener::$title);
        $this->assertEquals(self::EMPTY_NAME, MockUpPersonWithRoleListener::$name);
        $this->assertEquals(self::EMPTY_CODE, MockUpPersonWithRoleListener::$code);

        $title = 'test title';
        $name = 'Bob';
        $code = 'actor';

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);

        $person = new Person();
        $person->setName($name);

        $role = new Role();
        $role->setCod($code);

        $this->personWithRoleDispatcher->dispatchCreate($multimediaObject, $person, $role);

        $this->assertTrue(MockUpPersonWithRoleListener::$called);
        $this->assertEquals($title, MockUpPersonWithRoleListener::$title);
        $this->assertEquals($name, MockUpPersonWithRoleListener::$name);
        $this->assertEquals($code, MockUpPersonWithRoleListener::$code);
    }

    public function testDispatchUpdate()
    {
        $this->dispatcher->addListener(SchemaEvents::PERSONWITHROLE_UPDATE, function ($event, $name) {
            $this->assertInstanceOf(PersonWithRoleEvent::class, $event);
            $this->assertEquals(SchemaEvents::PERSONWITHROLE_UPDATE, $name);

            $multimediaObject = $event->getMultimediaObject();
            $person = $event->getPerson();
            $role = $event->getRole();

            MockUpPersonWithRoleListener::$called = true;
            MockUpPersonWithRoleListener::$title = $multimediaObject->getTitle();
            MockUpPersonWithRoleListener::$name = $person->getName();
            MockUpPersonWithRoleListener::$code = $role->getCod();
        });

        $this->assertFalse(MockUpPersonWithRoleListener::$called);
        $this->assertEquals(self::EMPTY_TITLE, MockUpPersonWithRoleListener::$title);
        $this->assertEquals(self::EMPTY_NAME, MockUpPersonWithRoleListener::$name);
        $this->assertEquals(self::EMPTY_CODE, MockUpPersonWithRoleListener::$code);

        $title = 'test title';
        $name = 'Bob';
        $code = 'actor';

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);

        $person = new Person();
        $person->setName($name);

        $role = new Role();
        $role->setCod($code);

        $updateName = 'Bob Anderson';
        $person->setName($updateName);

        $this->personWithRoleDispatcher->dispatchUpdate($multimediaObject, $person, $role);

        $this->assertTrue(MockUpPersonWithRoleListener::$called);
        $this->assertEquals($title, MockUpPersonWithRoleListener::$title);
        $this->assertEquals($updateName, MockUpPersonWithRoleListener::$name);
        $this->assertEquals($code, MockUpPersonWithRoleListener::$code);
    }

    public function testDispatchDelete()
    {
        $this->dispatcher->addListener(SchemaEvents::PERSONWITHROLE_DELETE, function ($event, $name) {
            $this->assertInstanceOf(PersonWithRoleEvent::class, $event);
            $this->assertEquals(SchemaEvents::PERSONWITHROLE_DELETE, $name);

            $multimediaObject = $event->getMultimediaObject();
            $person = $event->getPerson();
            $role = $event->getRole();

            MockUpPersonWithRoleListener::$called = true;
            MockUpPersonWithRoleListener::$title = $multimediaObject->getTitle();
            MockUpPersonWithRoleListener::$name = $person->getName();
            MockUpPersonWithRoleListener::$code = $role->getCod();
        });

        $this->assertFalse(MockUpPersonWithRoleListener::$called);
        $this->assertEquals(self::EMPTY_TITLE, MockUpPersonWithRoleListener::$title);
        $this->assertEquals(self::EMPTY_NAME, MockUpPersonWithRoleListener::$name);
        $this->assertEquals(self::EMPTY_CODE, MockUpPersonWithRoleListener::$code);

        $title = 'test title';
        $name = 'Bob';
        $code = 'actor';

        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);

        $person = new Person();
        $person->setName($name);

        $role = new Role();
        $role->setCod($code);

        $this->personWithRoleDispatcher->dispatchDelete($multimediaObject, $person, $role);

        $this->assertTrue(MockUpPersonWithRoleListener::$called);
        $this->assertEquals($title, MockUpPersonWithRoleListener::$title);
        $this->assertEquals($name, MockUpPersonWithRoleListener::$name);
        $this->assertEquals($code, MockUpPersonWithRoleListener::$code);
    }
}

class MockUpPersonWithRoleListener
{
    public static $called = false;
    public static $title = PersonWithRoleEventDispatcherServiceTest::EMPTY_TITLE;
    public static $name = PersonWithRoleEventDispatcherServiceTest::EMPTY_NAME;
    public static $code = PersonWithRoleEventDispatcherServiceTest::EMPTY_CODE;
}
