<?php

namespace Pumukit\AdminBundle\Form\Type;

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
      ->add('fullname', 'text', array('attr' => array('style' => 'width: 420px'), 'label' => 'Nombre'))
      ->add('username', 'text', array(
                'attr' => array(
                    'pattern' => "^[a-zA-Z0-9_]{4,16}$",
                    'oninvalid' => "setCustomValidity('El username no puede tener espacios en blanco ni caracteres especiales')",
                    'oninput' => "setCustomValidity('')",
                    'style' => 'width: 420px'), 
                'label' => 'Username'))
      ->add('plain_password', 'password', array(
		'required' => false,
		'label' => 'Password',
                'attr' => array('style' => 'width: 420px')))
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
      ->add('email', 'email', array('attr' => array('style' => 'width: 420px'), 'label' => 'Email'))
      ->add('roles', 'choice', array(
                    'choices' => array(
                        'ROLE_SUPER_ADMIN' => 'Administrador',
                        'ROLE_ADMIN' => 'Publicador'),
		    'multiple' => true,
		    'expanded' => true,
                    'attr' => array('style' => 'width: 420px'),
                    'label' => 'Tipo'));
  }
  

  public function setDefaultOptions(OptionsResolverInterface $resolver)
  {
    $resolver->setDefaults(array(
        'data_class' => 'Pumukit\SchemaBundle\Document\User'
    ));
  }

  public function getName()
  {
    return 'pumukitadmin_user';
  }
}