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
    private $dispatcher;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
        $this->dispatcher = new EventDispatcher();
        MockUpFormListener::$called = false;
        MockUpFormListener::$title = self::EMPTY_TITLE;
        $this->formDispatcher = new FormEventDispatcherService($this->dispatcher);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dispatcher = null;
        $this->formDispatcher = null;
        gc_collect_cycles();
    }

    public function testDispatchSubmit()
    {
        $this->dispatcher->addListener(WizardEvents::FORM_SUBMIT, function ($event, $title) {
            $this->assertInstanceOf(FormEvent::class, $event);
            $this->assertEquals(WizardEvents::FORM_SUBMIT, $title);
            $form = $event->getForm();
            MockUpFormListener::$called = true;
            MockUpFormListener::$title = $form['title'];
            $user = $event->getUser();
            $this->assertInstanceOf(User::class, $user);
            $multimediaObject = $event->getMultimediaObject();
            $this->assertInstanceOf(MultimediaObject::class, $multimediaObject);
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
