<?php

namespace Pumukit\WizardBundle\Services;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Pumukit\WizardBundle\Event\WizardEvents;
use Pumukit\WizardBundle\Event\FormEvent;

class FormEventDispatcherService
{
    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * Constructor.
     *
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Dispatch submitted form.
     *
     * Dispatchs the event FORM_SUBMIT
     * 'wizard.form.submit' passing
     * the submitted form
     *
     * @param array $form
     */
    public function dispatchSubmit(array $form)
    {
        $event = new FormEvent($form);
        $this->dispatcher->dispatch(WizardEvents::FORM_SUBMIT, $event);
    }
}
