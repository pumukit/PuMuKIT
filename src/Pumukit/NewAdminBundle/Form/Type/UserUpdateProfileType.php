<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Form\Type;

use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserUpdateProfileType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $user = $builder->getData();
        $builder
            ->add('enabled', HiddenType::class, ['data' => true])
            ->add(
                'fullname',
                TextType::class,
                [
                    'attr' => ['aria-label' => $this->translator->trans('Name and Surname', [], null, $this->locale)],
                    'disabled' => !$user->isLocal(),
                    'label' => $this->translator->trans('Name and Surname', [], null, $this->locale),
                ]
            )
            ->add(
                'username',
                TextType::class,
                [
                    'disabled' => true,
                    'attr' => ['aria-label' => $this->translator->trans('Username', [], null, $this->locale)],
                    'label' => $this->translator->trans('Username', [], null, $this->locale),
                ]
            )
            ->add(
                'plain_password',
                RepeatedType::class,
                [
                    'type' => PasswordType::class,
                    'invalid_message' => $this->translator->trans('The password fields must match.'),
                    'first_options' => ['label' => $this->translator->trans('Password')],
                    'second_options' => ['label' => $this->translator->trans('Repeat Password')],
                    'attr' => ['autocomplete' => 'off', 'aria-label' => $this->translator->trans('Password', [], null, $this->locale)],
                    'disabled' => !$user->isLocal(),
                    'required' => false,
                    'label' => $this->translator->trans('Password', [], null, $this->locale),
                ]
            )
            ->add(
                'email',
                EmailType::class,
                [
                    'disabled' => true,
                    'attr' => ['aria-label' => $this->translator->trans('Email', [], null, $this->locale)],
                    'label' => $this->translator->trans('Email', [], null, $this->locale),
                ]
            )
            ->add(
                'permissionProfile',
                null,
                [
                    'disabled' => !$user->hasRole('ROLE_ACCESS_ADMIN_USERS'),
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => User::class,
            ]
        );

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix(): string
    {
        return 'pumukitnewadmin_user';
    }
}
