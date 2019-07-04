<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Pumukit\NewAdminBundle\Form\Type\Base\TextareaI18nType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nAdvanceType;
use Pumukit\NewAdminBundle\Form\Type\Other\Html5dateType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MultimediaObjectTemplateMetaType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $builder
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
                'i18n_keyword',
                TextI18nAdvanceType::class,
                [
                    'required' => false,
                    'attr' => ['class' => 'mmobj materialtags', 'aria-label' => $this->translator->trans('Keywords', [], null, $this->locale)],
                    'label' => $this->translator->trans('Keywords', [], null, $this->locale),
                ]
            )
            ->add(
                'copyright',
                TextType::class,
                [
                    'required' => false,
                    'attr' => ['aria-label' => $this->translator->trans('Copyright', [], null, $this->locale)],
                    'label' => $this->translator->trans('Copyright', [], null, $this->locale),
                ]
            )
            ->add(
                'public_date',
                Html5dateType::class,
                [
                    'data_class' => 'DateTime',
                    'attr' => ['aria-label' => $this->translator->trans('Publication Date', [], null, $this->locale)],
                    'label' => $this->translator->trans('Publication Date', [], null, $this->locale),
                ]
            )
            ->add(
                'record_date',
                Html5dateType::class,
                [
                    'data_class' => 'DateTime',
                    'attr' => ['aria-label' => $this->translator->trans('Recording Date', [], null, $this->locale)],
                    'label' => $this->translator->trans('Recording Date', [], null, $this->locale),
                ]
            )
            ->add(
                'i18n_line2',
                TextareaI18nType::class,
                [
                    'required' => false,
                    'attr' => [
                        'groupclass' => 'hidden-naked',
                        'style' => 'resize:vertical;',
                        'aria-label' => $this->translator->trans('Headline', [], null, $this->locale),
                    ],
                    'label' => $this->translator->trans('Headline', [], null, $this->locale),
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Pumukit\SchemaBundle\Document\MultimediaObject',
            ]
        );

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_mmtemplate_meta';
    }
}
