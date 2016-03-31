<?php

namespace Pumukit\SchemaBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Event\PersonWithRoleEvent;
use Pumukit\SchemaBundle\Services\PersonWithRoleEventDispatcherService;

class PersonWithRoleEventDispatcherServiceTest extends WebTestCase
{
    const EMPTY_TITLE = 'EMTPY TITLE';
    const EMPTY_NAME = 'EMTPY NAME';
    const EMPTY_CODE = 'EMTPY CODE';

    private $personWithRoleDispatcher;

    public function __construct()
    {
        $options = array('environment' => 'test');
        static::bootKernel($options);

        $this->dispatcher = static::$kernel->getContainer()
          ->get('event_dispatcher');
    }

    public function setUp()
    {
        MockUpPersonWithRoleListener::$called = false;
        MockUpPersonWithRoleListener::$title = PersonWithRoleEventDispatcherServiceTest::EMPTY_TITLE;
        MockUpPersonWithRoleListener::$name = PersonWithRoleEventDispatcherServiceTest::EMPTY_NAME;
        MockUpPersonWithRoleListener::$code = PersonWithRoleEventDispatcherServiceTest::EMPTY_CODE;

        $this->personWithRoleDispatcher = new PersonWithRoleEventDispatcherService($this->dispatcher);
    }

    public function testDispatchCreate()
    {
        $this->dispatcher->addListener(SchemaEvents::PERSONWITHROLE_CREATE, function($event, $name)
                                       {
                                           $this->assertTrue($event instanceof PersonWithRoleEvent);
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
        $this->assertEquals(PersonWithRoleEventDispatcherServiceTest::EMPTY_TITLE, MockUpPersonWithRoleListener::$title);
        $this->assertEquals(PersonWithRoleEventDispatcherServiceTest::EMPTY_NAME, MockUpPersonWithRoleListener::$name);
        $this->assertEquals(PersonWithRoleEventDispatcherServiceTest::EMPTY_CODE, MockUpPersonWithRoleListener::$code);

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
        $this->dispatcher->addListener(SchemaEvents::PERSONWITHROLE_UPDATE, function($event, $name)
                                       {
                                           $this->assertTrue($event instanceof PersonWithRoleEvent);
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
        $this->assertEquals(PersonWithRoleEventDispatcherServiceTest::EMPTY_TITLE, MockUpPersonWithRoleListener::$title);
        $this->assertEquals(PersonWithRoleEventDispatcherServiceTest::EMPTY_NAME, MockUpPersonWithRoleListener::$name);
        $this->assertEquals(PersonWithRoleEventDispatcherServiceTest::EMPTY_CODE, MockUpPersonWithRoleListener::$code);

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
        $this->dispatcher->addListener(SchemaEvents::PERSONWITHROLE_DELETE, function($event, $name)
                                       {
                                           $this->assertTrue($event instanceof PersonWithRoleEvent);
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
        $this->assertEquals(PersonWithRoleEventDispatcherServiceTest::EMPTY_TITLE, MockUpPersonWithRoleListener::$title);
        $this->assertEquals(PersonWithRoleEventDispatcherServiceTest::EMPTY_NAME, MockUpPersonWithRoleListener::$name);
        $this->assertEquals(PersonWithRoleEventDispatcherServiceTest::EMPTY_CODE, MockUpPersonWithRoleListener::$code);

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
    static public $called = false;
    static public $title = PersonWithRoleEventDispatcherServiceTest::EMPTY_TITLE;
    static public $name = PersonWithRoleEventDispatcherServiceTest::EMPTY_NAME;
    static public $code = PersonWithRoleEventDispatcherServiceTest::EMPTY_CODE;
}