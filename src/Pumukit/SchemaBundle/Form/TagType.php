<?php

namespace Pumukit\SchemaBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TagType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title')
            ->add('description')
            ->add('slug')
            ->add('cod')
            ->add('metatag')
            ->add('created')
            ->add('updated')
            ->add('multimedia_objects')
            ->add('parent')
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pumukit\SchemaBundle\Entity\Tag'
        ));
    }

    public function getName()
    {
        return 'pumukit_schemabundle_tagtype';
    }
}
