<?php

namespace Pumukit\SchemaBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PersonType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username')
            ->add('password', 'repeated', array(
               'first_name'  => 'password',
               'second_name' => 'confirm',
               'type'        => 'password'))
            ->add('email')
            ->add('name')
            ->add('web')
            ->add('phone')
            ->add('honorific')
            ->add('firm')
            ->add('post')
            ->add('bio', 'textarea')
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pumukit\SchemaBundle\Entity\Person'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'pumukit_schemabundle_persontype';
    }
}
