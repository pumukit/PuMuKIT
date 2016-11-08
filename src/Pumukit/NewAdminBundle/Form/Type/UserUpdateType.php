<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

class UserUpdateType extends AbstractType
{
    private $translator;
    private $locale;

    public function __construct(TranslatorInterface $translator, $locale = 'en')
    {
        $this->translator = $translator;
        $this->locale = $locale;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $builder->getData();
        $builder
            ->add('enabled', 'hidden', array('data' => true))
            ->add('fullname', 'text',
                  array(
                      'disabled' => !$user->isLocal(),
                      'label' => $this->translator->trans('Name and Surname', array(), null, $this->locale), ))
            ->add('username', 'text',
                  array(
                      'disabled' => true,
                      'label' => $this->translator->trans('Username', array(), null, $this->locale), ))
            ->add('plain_password', 'password',
                  array(
                      'disabled' => !$user->isLocal(),
                      'attr' => array('autocomplete' => 'off'),
                      'required' => false,
                      'label' => $this->translator->trans('Password', array(), null, $this->locale), ))
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
                  array(
                      'disabled' => !$user->isLocal(),
                      'label' => $this->translator->trans('Email', array(), null, $this->locale), ))
            ->add('permissionProfile', null,
                  array(
                      'label' => $this->translator->trans('Permission Profile', array(), null, $this->locale), ));

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $user = $event->getData();
            if ($user->hasRole('ROLE_SUPER_ADMIN')) {
                $event->getForm()->remove('permissionProfile');
                $event->getForm()->add('permissionProfilePlacebo', 'choice',
                    array(
                        'mapped' => false,
                        'choices' => array('ROLE_SUPER_ADMIN' => 'System Super Administrator'),
                        'label' => $this->translator->trans('Permission Profile', array(), null, $this->locale), ));
            }
        });
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
