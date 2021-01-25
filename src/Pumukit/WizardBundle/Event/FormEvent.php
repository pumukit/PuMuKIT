<?php

declare(strict_types=1);

namespace Pumukit\WizardBundle\Event;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\EventDispatcher\Event;

class FormEvent extends Event
{
    protected $form;
    protected $multimediaObject;
    protected $user;

    public function __construct(User $user, MultimediaObject $multimediaObject, array $form)
    {
        $this->user = $user;
        $this->multimediaObject = $multimediaObject;
        $this->form = $form;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getMultimediaObject(): MultimediaObject
    {
        return $this->multimediaObject;
    }

    public function getForm(): array
    {
        return $this->form;
    }
}
