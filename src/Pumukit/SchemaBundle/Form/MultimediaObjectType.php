<?php

namespace Pumukit\SchemaBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MultimediaObjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('rank')
            ->add('status')
            ->add('record_date')
            ->add('public_date')
            ->add('title')
            ->add('subtitle')
            ->add('description')
            ->add('line2')
            ->add('copyright')
            ->add('keyword')
            ->add('duration')
            ->add('series')
            ->add('tags')
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pumukit\SchemaBundle\Entity\MultimediaObject'
        ));
    }

    public function getName()
    {
        return 'pumukit_schemabundle_multimediaobjecttype';
    }
}
