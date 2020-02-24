<?php

namespace Pumukit\WizardBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\WizardBundle\Event\FormEvent;
use Pumukit\WizardBundle\Event\WizardEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FormEventDispatcherService
{
    /** @var EventDispatcherInterface */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Dispatch the event FORM_SUBMIT 'wizard.form.submit' passing the submitted form.
     */
    public function dispatchSubmit(User $user, MultimediaObject $multimediaObject, array $form): void
    {
        $event = new FormEvent($user, $multimediaObject, $form);
        $this->dispatcher->dispatch($event, WizardEvents::FORM_SUBMIT);
    }
}
