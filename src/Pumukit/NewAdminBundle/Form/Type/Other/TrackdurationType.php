<?php

namespace Pumukit\NewAdminBundle\Form\Type\Other;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TrackdurationType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'compound' => false,
        ));
    }

    public function getBlockPrefix()
    {
        return 'trackduration';
    }
}
