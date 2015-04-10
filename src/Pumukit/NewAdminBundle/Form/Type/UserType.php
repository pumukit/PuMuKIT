<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Pumukit\SchemaBundle\Document\User;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
      ->add('enabled', 'hidden', array('data' => true))
      ->add('fullname', 'text', array('label' => 'Name'))
      ->add('username', 'text', array(
                'attr' => array(
                    'pattern' => "^[a-zA-Z0-9_]{4,16}$",
                    'oninvalid' => "setCustomValidity('The username can not have blank spaces neither special characters')",
                    'oninput' => "setCustomValidity('')"),
                'label' => 'Username', ))
      ->add('plain_password', 'password', array('required' => false, 'label' => 'Password'))
      /*
      ->add('plain_password', 'repeated', array(
        'type' => 'password',
        'options' => array('attr' => array('oninvalid' => "setCustomValidity('password-field')")),
        'required' => false,
        'invalid_message' => 'The password fields must match.',
        'first_options'  => array('label' => 'Password'),
        'second_options' => array('label' => 'Repita Password'),
                'attr' => array('style' => 'width: 420px')))
      */
      ->add('email', 'email', array('label' => 'Email'))
      ->add('roles', 'choice', array(
                    'choices' => array(
                        'ROLE_SUPER_ADMIN' => 'Administrator',
                        'ROLE_ADMIN' => 'Publisher', ),
            'multiple' => true,
            'expanded' => true,
                    'label' => 'Type', ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
        'data_class' => 'Pumukit\SchemaBundle\Document\User',
    ));
    }

    public function getName()
    {
        return 'pumukitnewadmin_user';
    }
}
