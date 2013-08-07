<?php

namespace Pumukit\SchemaBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\Validator\Constraints\Length;

class PersonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('login')
            ->add('password', 'repeated', array(
               'first_name'  => 'password',
               'second_name' => 'confirm',
               'type'        => 'password'))
            ->add('email')
             ->add('honorific')
            ->add('name')
            ->add('web')
            ->add('phone')
            ->add('firm')
            ->add('post')
            ->add('bio', 'textarea')
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pumukit\SchemaBundle\Entity\Person'
        ));
    }

    public function getName()
    {
        return 'pumukit_schemabundle_persontype';
    }
}
