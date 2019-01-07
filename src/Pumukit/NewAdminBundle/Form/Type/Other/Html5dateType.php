<?php

namespace Pumukit\NewAdminBundle\Form\Type\Other;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Html5dateType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'compound' => false,
        ));
    }

    /*
    public function getParent()
    {
        return 'form';
    }
    */

    public function getBlockPrefix()
    {
        return 'html5date';
    }
}
