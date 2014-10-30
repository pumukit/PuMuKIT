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
      ->add('fullname', 'text', array('attr' => array('style' => 'width: 420px'), 'label' => 'Nombre'))
      ->add('username', 'text', array(
                'attr' => array(
                    'pattern' => "^[a-zA-Z0-9_]{4,16}$",
                    'oninvalid' => "setCustomValidity('El username no puede tener espacios en blanco ni caracteres especiales')",
                    'oninput' => "setCustomValidity('')",
                    'style' => 'width: 420px'), 
                'label' => 'Username'))
      ->add('password', 'repeated', array(
		'type' => 'password',
		'invalid_message' => 'The password fields must match.',
		'options' => array('attr' => array('class' => 'password-field')),
		'required' => true,
		'first_options'  => array('label' => 'Password'),
		'second_options' => array('label' => 'Repeat Password'),
                'attr' => array('style' => 'width: 420px')))
      ->add('email', 'email', array('attr' => array('style' => 'width: 420px'), 'label' => 'Email'))
      ->add('user_type', 'choice', array(
                'choices' => array(
                    User::USER_TYPE_ADMIN => 'Administrador',
                    User::USER_TYPE_PUB => 'Publicador',
                    User::USER_TYPE_FTP => 'FTP'),
                'attr' => array('style' => 'width: 420px'),
                'label' => 'Tipo'))
      ->add('root', 'checkbox', array('required'=>false, 'label' => 'Root'));
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