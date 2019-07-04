<?php

namespace Pumukit\WizardBundle\Event;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\EventDispatcher\Event;

class FormEvent extends Event
{
    /**
     * @var array
     */
    protected $form;

    /**
     * @var MultimediaObject
     */
    protected $multimediaObject;

    /**
     * @var User
     */
    protected $user;

    /**
     * @param array $form
     */
    public function __construct(User $user, MultimediaObject $multimediaObject, array $form)
    {
        $this->user = $user;
        $this->multimediaObject = $multimediaObject;
        $this->form = $form;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return MultimediaObject
     */
    public function getMultimediaObject()
    {
        return $this->multimediaObject;
    }

    /**
     * @return array
     */
    public function getForm()
    {
        return $this->form;
    }
}
