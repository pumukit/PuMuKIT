<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\Translation\TranslatorInterface;

class UserType extends AbstractType
{
    private $translator;
    private $locale;

    public function __construct(TranslatorInterface $translator, $locale='en')
    {
        $this->translator = $translator;
        $this->locale = $locale;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('enabled', 'hidden', array('data' => true))
            ->add('fullname', 'text',
                  array('label' => $this->translator->trans('Name', array(), null, $this->locale)))
            ->add('username', 'text',
                  array(
                        'attr' => array(
                                        'pattern' => "^[a-zA-Z0-9_]{4,16}$",
                                        'oninvalid' => "setCustomValidity('The username can not have blank spaces neither special characters')",
                                        'oninput' => "setCustomValidity('')"),
                        'label' => $this->translator->trans('Username', array(), null, $this->locale)))
            ->add('plain_password', 'password',
                  array(
                        'required' => false,
                        'label' => $this->translator->trans('Password', array(), null, $this->locale)))
          /* TODO check password
            ->add('plain_password', 'repeated', array(
            'type' => 'password',
            'options' => array('attr' => array('oninvalid' => "setCustomValidity('password-field')")),
            'required' => false,
            'invalid_message' => 'The password fields must match.',
            'first_options'  => array('label' => 'Password'),
            'second_options' => array('label' => 'Repita Password'),
            'attr' => array('style' => 'width: 420px')))
          */
            ->add('email', 'email',
                  array('label' => $this->translator->trans('Email', array(), null, $this->locale)))
            ->add('permissionProfile', null,
                  array('label' => $this->translator->trans('Permission Profile', array(), null, $this->locale)));
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
