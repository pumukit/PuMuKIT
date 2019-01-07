<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserUpdateType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $user = $builder->getData();
        $builder
            ->add('enabled', HiddenType::class, array('data' => true))
            ->add('fullname', TextType::class,
                  array(
                      'attr' => array('aria-label' => $this->translator->trans('Name and Surname', array(), null, $this->locale)),
                      'disabled' => !$user->isLocal(),
                      'label' => $this->translator->trans('Name and Surname', array(), null, $this->locale), ))
            ->add('username', TextType::class,
                  array(
                      'disabled' => true,
                      'attr' => array('aria-label' => $this->translator->trans('Username', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Username', array(), null, $this->locale), ))
            ->add('plain_password', PasswordType::class,
                  array(
                      'attr' => array('autocomplete' => 'off', 'aria-label' => $this->translator->trans('Password', array(), null, $this->locale)),
                      'disabled' => !$user->isLocal(),
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
            ->add('email', EmailType::class,
                  array(
                      'attr' => array('aria-label' => $this->translator->trans('Email', array(), null, $this->locale)),
                      'disabled' => !$user->isLocal(),
                      'label' => $this->translator->trans('Email', array(), null, $this->locale), ))
            ->add('permissionProfile', null,
                  array(
                      'attr' => array('aria-label' => $this->translator->trans('Permission Profile', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Permission Profile', array(), null, $this->locale), ));

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $user = $event->getData();
            if ($user->hasRole('ROLE_SUPER_ADMIN')) {
                $event->getForm()->remove('permissionProfile');
                $event->getForm()->add('permissionProfilePlacebo', ChoiceType::class,
                                       array(
                                           'mapped' => false,
                                           'choices' => array('System Super Administrator' => 'ROLE_SUPER_ADMIN'),
                                           'attr' => array('aria-label' => $this->translator->trans('Permission Profile', array(), null, $this->locale)),
                                           'label' => $this->translator->trans('Permission Profile', array(), null, $this->locale), ));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pumukit\SchemaBundle\Document\User',
        ));

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_user';
    }
}
