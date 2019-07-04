<?php

namespace Pumukit\WizardBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\WizardBundle\Event\FormEvent;
use Pumukit\WizardBundle\Event\WizardEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FormEventDispatcherService
{
    /**
     * @var EventDispatcherInterface
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
     * @param User             $user
     * @param MultimediaObject $multimediaObject
     * @param array            $form
     */
    public function dispatchSubmit(User $user, MultimediaObject $multimediaObject, array $form)
    {
        $event = new FormEvent($user, $multimediaObject, $form);
        $this->dispatcher->dispatch(WizardEvents::FORM_SUBMIT, $event);
    }
}
