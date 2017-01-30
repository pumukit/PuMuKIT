<?php

namespace Pumukit\WizardBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Pumukit\WizardBundle\Document\Series;

class FormEvent extends Event
{
    /**
     * @var array
     */
    protected $form;

    /**
     * @param array $form
     */
    public function __construct(array $form)
    {
        $this->form = $form;
    }

    /**
     * @return array
     */
    public function getForm()
    {
        return $this->form;
    }
}
