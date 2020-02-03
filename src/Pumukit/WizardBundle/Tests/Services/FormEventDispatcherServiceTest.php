<?php

namespace Pumukit\WizardBundle\Tests\Services;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\WizardBundle\Event\FormEvent;
use Pumukit\WizardBundle\Event\WizardEvents;
use Pumukit\WizardBundle\Services\FormEventDispatcherService;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 * @coversNothing
 */
class FormEventDispatcherServiceTest extends PumukitTestCase
{
    const EMPTY_TITLE = 'EMTPY TITLE';

    private $formDispatcher;
    private $dm;
    private $dispatcher;

    public function setUp()
    {
        $this->dm = parent::setUp();
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        $this->dispatcher = new EventDispatcher();
        MockUpFormListener::$called = false;
        MockUpFormListener::$title = self::EMPTY_TITLE;
        $this->formDispatcher = new FormEventDispatcherService($this->dispatcher);
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->dispatcher = null;
        $this->formDispatcher = null;
        gc_collect_cycles();
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
