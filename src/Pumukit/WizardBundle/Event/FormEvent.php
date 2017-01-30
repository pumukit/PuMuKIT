<?php

namespace Pumukit\WizardBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Pumukit\SchemaBundle\Document\MultimediaObject;

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
     * @param array $form
     */
    public function __construct(MultimediaObject $multimediaObject, array $form)
    {
        $this->multimediaObject = $multimediaObject;
        $this->form = $form;
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
