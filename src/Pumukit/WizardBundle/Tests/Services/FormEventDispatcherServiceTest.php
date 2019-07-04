<?php

namespace Pumukit\WizardBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\WizardBundle\Event\WizardEvents;
use Pumukit\WizardBundle\Event\FormEvent;
use Pumukit\WizardBundle\Services\FormEventDispatcherService;

class FormEventDispatcherServiceTest extends WebTestCase
{
    const EMPTY_TITLE = 'EMTPY TITLE';

    private $formDispatcher;
    private $dm;
    private $dispatcher;

    public function setUp()
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        $this->dm = static::$kernel->getContainer()
          ->get('doctrine_mongodb.odm.document_manager');
        $this->dispatcher = new EventDispatcher();
        MockUpFormListener::$called = false;
        MockUpFormListener::$title = self::EMPTY_TITLE;
        $this->formDispatcher = new FormEventDispatcherService($this->dispatcher);
    }

    public function tearDown()
    {
        $this->dispatcher = null;
        $this->formDispatcher = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testDispatchSubmit()
    {
        $this->dispatcher->addListener(WizardEvents::FORM_SUBMIT, function ($event, $title) {
            $this->assertTrue($event instanceof FormEvent);
            $this->assertEquals(WizardEvents::FORM_SUBMIT, $title);
            $form = $event->getForm();
            MockUpFormListener::$called = true;
            MockUpFormListener::$title = $form['title'];
            $user = $event->getUser();
            $this->assertTrue($user instanceof User);
            $multimediaObject = $event->getMultimediaObject();
            $this->assertTrue($multimediaObject instanceof MultimediaObject);
        });
        $this->assertFalse(MockUpFormListener::$called);
        $this->assertEquals(self::EMPTY_TITLE, MockUpFormListener::$title);
        $title = 'test title';
        $multimediaObject = new MultimediaObject();
        $multimediaObject->setTitle($title);
        $form = ['title' => $title];
        $user = new User();
        $this->formDispatcher->dispatchSubmit($user, $multimediaObject, $form);
        $this->assertTrue(MockUpFormListener::$called);
        $this->assertEquals($title, MockUpFormListener::$title);
    }
}

class MockUpFormListener
{
    public static $called = false;
    public static $title = FormEventDispatcherServiceTest::EMPTY_TITLE;
}
