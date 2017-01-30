<?php

namespace Pumukit\WizardBundle\Services;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
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
     * @param MultimediaObject $multimediaObject
     * @param array            $form
     */
    public function dispatchSubmit(MultimediaObject $multimediaObject, array $form)
    {
        $event = new FormEvent($multimediaObject, $form);
        $this->dispatcher->dispatch(WizardEvents::FORM_SUBMIT, $event);
    }
}
