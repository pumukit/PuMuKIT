<?php

declare(strict_types=1);

namespace Pumukit\WizardBundle\Event;

final class WizardEvents
{
    /**
     * The wizard.form.submit event is thrown each time a
     * wizard form is submitted.
     *
     * The event listener receives a
     * Pumukit\WizardBundle\Event\FormEvent instance.
     *
     * @var string
     */
    const FORM_SUBMIT = 'wizard.form.submit';
}
