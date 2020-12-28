<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Form\Type\Other;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventscheduleType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'compound' => false,
                'multiple' => false,
            ]
        );
    }

    public function getBlockPrefix()
    {
        return 'eventschedule';
    }
}
