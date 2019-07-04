<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Pumukit\NewAdminBundle\Form\Type\Base\TextareaI18nType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $builder
            ->add(
                'i18n_honorific',
                TextI18nType::class,
                [
                    'required' => false,
                    'attr' => ['aria-label' => $this->translator->trans('Honorific', [], null, $this->locale)],
                    'label' => $this->translator->trans('Honorific', [], null, $this->locale),
                ]
            )
            ->add(
                'name',
                TextType::class,
                [
                    'attr' => ['aria-label' => $this->translator->trans('Name', [], null, $this->locale)],
                    'label' => $this->translator->trans('Name', [], null, $this->locale),
                ]
            )
            ->add(
                'i18n_post',
                TextI18nType::class,
                [
                    'required' => false,
                    'attr' => ['aria-label' => $this->translator->trans('Post', [], null, $this->locale)],
                    'label' => $this->translator->trans('Post', [], null, $this->locale),
                ]
            )
            ->add(
                'i18n_firm',
                TextI18nType::class,
                [
                    'required' => false,
                    'attr' => ['aria-label' => $this->translator->trans('Firm', [], null, $this->locale)],
                    'label' => $this->translator->trans('Firm', [], null, $this->locale),
                ]
            )
            ->add(
                'i18n_bio',
                TextareaI18nType::class,
                [
                    'required' => false,
                    'attr' => ['style' => 'resize:vertical;', 'aria-label' => $this->translator->trans('Bio', [], null, $this->locale)],
                    'label' => $this->translator->trans('Bio', [], null, $this->locale),
                ]
            )
            ->add(
                'email',
                EmailType::class,
                [
                    'required' => false,
                    'attr' => ['aria-label' => $this->translator->trans('Email', [], null, $this->locale)],
                    'label' => $this->translator->trans('Email', [], null, $this->locale),
                ]
            )
            ->add(
                'web',
                UrlType::class,
                [
                    'required' => false,
                    'attr' => ['aria-label' => $this->translator->trans('Web', [], null, $this->locale)],
                    'label' => $this->translator->trans('Web', [], null, $this->locale),
                ]
            )
            ->add(
                'phone',
                TextType::class,
                [
                    'required' => false,
                    'attr' => ['aria-label' => $this->translator->trans('Phone', [], null, $this->locale)],
                    'label' => $this->translator->trans('Phone', [], null, $this->locale),
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Pumukit\SchemaBundle\Document\Person',
            ]
        );

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_person';
    }
}
