<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $builder
            ->add('enabled', HiddenType::class, ['data' => true])
            ->add(
                'fullname',
                TextType::class,
                [
                    'attr' => ['aria-label' => $this->translator->trans('Name and Surname', [], null, $this->locale)],
                    'label' => $this->translator->trans('Name and Surname', [], null, $this->locale),
                ]
            )
            ->add(
                'username',
                TextType::class,
                [
                    'attr' => [
                        'aria-label' => $this->translator->trans('Username', [], null, $this->locale),
                        'autocomplete' => 'off',
                        'pattern' => '^[a-zA-Z0-9_\-\.@]{4,32}$',
                        'oninvalid' => "setCustomValidity('The username can not have blank spaces neither special characters')",
                        'oninput' => "setCustomValidity('')",
                    ],
                    'label' => $this->translator->trans('Username', [], null, $this->locale),
                ]
            )
            ->add(
                'plain_password',
                PasswordType::class,
                [
                    'attr' => [
                        'aria-label' => $this->translator->trans('Password', [], null, $this->locale),
                        'autocomplete' => 'off',
                    ],
                    'required' => true,
                    'label' => $this->translator->trans('Password', [], null, $this->locale),
                ]
            )
            ->add(
                'email',
                EmailType::class,
                [
                    'attr' => ['aria-label' => $this->translator->trans('Email', [], null, $this->locale)],
                    'label' => $this->translator->trans('Email', [], null, $this->locale),
                ]
            )
            ->add(
                'permissionProfile',
                null,
                [
                    'attr' => ['aria-label' => $this->translator->trans('Permission Profile', [], null, $this->locale)],
                    'label' => $this->translator->trans('Permission Profile', [], null, $this->locale),
                ]
            )
        ;

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) {
                $user = $event->getData();
                if ($user->hasRole('ROLE_SUPER_ADMIN')) {
                    $event->getForm()->remove('permissionProfile');
                    $event->getForm()->add(
                        'permissionProfilePlacebo',
                        ChoiceType::class,
                        [
                            'mapped' => false,
                            'choices' => ['System Super Administrator' => 'ROLE_SUPER_ADMIN'],
                            'attr' => ['aria-label' => $this->translator->trans('Permission Profile', [], null, $this->locale)],
                            'label' => $this->translator->trans('Permission Profile', [], null, $this->locale),
                        ]
                    );
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Pumukit\SchemaBundle\Document\User',
            ]
        );

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_user';
    }
}
