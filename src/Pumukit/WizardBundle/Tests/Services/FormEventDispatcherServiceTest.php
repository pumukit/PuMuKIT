<?php

namespace Pumukit\WizardBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Pumukit\WizardBundle\Event\WizardEvents;
use Pumukit\WizardBundle\Event\FormEvent;
use Pumukit\WizardBundle\Services\FormEventDispatcherService;

class FormEventDispatcherServiceTest extends WebTestCase
{
    private $formDispatcher;

    public function setUp()
    {
        $options = array('environment' => 'test');
        static::bootKernel($options);
        $this->dm = static::$kernel->getContainer()
          ->get('doctrine_mongodb.odm.document_manager');
        $this->dispatcher = new EventDispatcher();
        MockUpFormListener::$called = false;
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
        });
        $this->assertFalse(MockUpFormListener::$called);
        $form = array('field1' => 'value1');
        $this->formDispatcher->dispatchSubmit($form);
        $this->assertTrue(MockUpFormListener::$called);
    }
}

class MockUpFormListener
{
    public static $called = false;
}
