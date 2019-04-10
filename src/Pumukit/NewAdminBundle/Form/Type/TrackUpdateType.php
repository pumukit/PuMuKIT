<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Pumukit\NewAdminBundle\Form\Type\Other\TrackresolutionType;
use Pumukit\NewAdminBundle\Form\Type\Other\TrackdurationType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nType;
use Pumukit\NewAdminBundle\Form\Type\Base\CustomLanguageType;

class TrackUpdateType extends AbstractType
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
                TextI18nType::class,
                [
                    'required' => false,
                    'attr' => ['aria-label' => $this->translator->trans('Description', [], null, $this->locale)],
                    'label' => $this->translator->trans('Description', [], null, $this->locale),
                ]
            )
            ->add(
                'hide',
                CheckboxType::class,
                [
                    'required' => false,
                    'attr' => ['aria-label' => $this->translator->trans('Hide', [], null, $this->locale)],
                    'label' => $this->translator->trans('Hide', [], null, $this->locale),
                ]
            )
            ->add(
                'allowDownload',
                CheckboxType::class,
                [
                    'required' => false,
                    'attr' => ['aria-label' => $this->translator->trans('Allow download', [], null, $this->locale)],
                    'label' => $this->translator->trans('Allow download', [], null, $this->locale),
                ]
            )
            ->add(
                'language',
                CustomLanguageType::class,
                [
                    'required' => true,
                    'attr' => ['aria-label' => $this->translator->trans('Video/Audio language', [], null, $this->locale)],
                    'label' => $this->translator->trans('Video/Audio language', [], null, $this->locale),
                ]
            )
            ->add(
                'durationinminutesandseconds',
                TrackdurationType::class,
                [
                    'required' => true,
                    'disabled' => true,
                    'attr' => ['aria-label' => $this->translator->trans('Duration', [], null, $this->locale)],
                    'label' => $this->translator->trans('Duration', [], null, $this->locale),
                ]
            )
            ->add(
                'resolution',
                TrackresolutionType::class,
                [
                    'required' => true,
                    'disabled' => true,
                    'attr' => ['aria-label' => $this->translator->trans('Resolution', [], null, $this->locale)],
                    'label' => $this->translator->trans('Resolution', [], null, $this->locale),
                ]
            )
            ->add(
                'size',
                IntegerType::class,
                [
                    'required' => true,
                    'disabled' => true,
                    'attr' => ['aria-label' => $this->translator->trans('Size', [], null, $this->locale)],
                    'label' => $this->translator->trans('Size', [], null, $this->locale),
                ]
            )
            ->add(
                'path',
                TextType::class,
                [
                    'required' => true,
                    'disabled' => true,
                    'attr' => ['aria-label' => $this->translator->trans('File', [], null, $this->locale)],
                    'label' => $this->translator->trans('File', [], null, $this->locale),
                ]
            )
            ->add(
                'url',
                TextType::class,
                [
                    'required' => true,
                    'disabled' => true,
                    'attr' => ['aria-label' => $this->translator->trans('URL', [], null, $this->locale)],
                    'label' => $this->translator->trans('URL', [], null, $this->locale),
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Pumukit\SchemaBundle\Document\Track',
            ]
        );

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_track_update';
    }
}
