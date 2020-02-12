<?php

namespace Pumukit\WizardBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\WizardBundle\Event\FormEvent;
use Pumukit\WizardBundle\Event\WizardEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;

class FormEventDispatcherService
{
    /**
     * Dispatch the event FORM_SUBMIT 'wizard.form.submit' passing the submitted form.
     */
    public function dispatchSubmit(User $user, MultimediaObject $multimediaObject, array $form): void
    {
        $event = new FormEvent($user, $multimediaObject, $form);
        $dispatcher = new EventDispatcher();
        $dispatcher->dispatch($event, WizardEvents::FORM_SUBMIT);
    }
}
