<?php

namespace Pumukit\WizardBundle\Event;

use Symfony\Component\EventDispatcher\Event;

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
