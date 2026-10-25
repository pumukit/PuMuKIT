<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Form\Type;

use Pumukit\NewAdminBundle\Form\Type\Base\TextareaI18nType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nType;
use Pumukit\SchemaBundle\Document\Live;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class LiveType.
 */
class LiveType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $builder
            ->add(
                'i18n_name',
                TextI18nType::class,
                [
                    'attr' => ['aria-label' => $this->translator->trans('Name', [], null, $this->locale)],
                    'label' => $this->translator->trans('Name', [], null, $this->locale),
                ]
            )
            ->add(
                'i18n_description',
                TextareaI18nType::class,
                [
                    'required' => false,
                    'attr' => ['style' => 'resize:vertical;', 'aria-label' => $this->translator->trans('Description', [], null, $this->locale)],
                    'label' => $this->translator->trans('Description', [], null, $this->locale),
                ]
            )
            ->add(
                'url',
                UrlType::class,
                [
                    'default_protocol' => 'http',
                    'attr' => ['aria-label' => $this->translator->trans('URL', [], null, $this->locale)],
                    'label' => $this->translator->trans('URL', [], null, $this->locale),
                ]
            )
            ->add(
                'source_name',
                TextType::class,
                [
                    'attr' => ['aria-label' => $this->translator->trans('STREAM', [], null, $this->locale)],
                    'label' => $this->translator->trans('STREAM', [], null, $this->locale),
                ]
            )
            ->add(
                'passwd',
                TextType::class,
                [
                    'required' => false,
                    'attr' => ['aria-label' => $this->translator->trans('Password', [], null, $this->locale)],
                    'label' => $this->translator->trans('Password', [], null, $this->locale),
                ]
            )
            ->add(
                'broadcasting',
                ChoiceType::class,
                [
                    'choice_translation_domain' => 'messages',
                    'choices' => ['On hold' => '0', 'Live Broadcasting' => '1'],
                    'attr' => ['aria-label' => $this->translator->trans('Status', [], null, $this->locale)],
                    'label' => $this->translator->trans('Status', [], null, $this->locale),
                ]
            )
            ->add(
                'live_type',
                ChoiceType::class,
                [
                    'attr' => ['aria-label' => $this->translator->trans('Technology', [], null, $this->locale)],
                    'choice_translation_domain' => 'messages',
                    'choices' => [
                        'WOWZA' => Live::LIVE_TYPE_WOWZA,
                    ],
                    'label' => $this->translator->trans('Technology', [], null, $this->locale),
                ]
            )
            ->add(
                'chat',
                CheckboxType::class,
                [
                    'required' => false,
                    'attr' => ['aria-label' => $this->translator->trans('Enable chat on this channel\'s page', [], null, $this->locale)],
                    'label' => $this->translator->trans('Enable chat on this channel\'s page', [], null, $this->locale),
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Live::class,
                'translation_domain' => false,
            ]
        );

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix(): string
    {
        return 'pumukitnewadmin_live';
    }
}
