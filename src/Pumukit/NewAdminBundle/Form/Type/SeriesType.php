<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Pumukit\NewAdminBundle\Form\Type\Base\TextareaI18nType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nAdvanceType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nType;
use Pumukit\NewAdminBundle\Form\Type\Other\Html5dateType;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SeriesType extends AbstractType
{
    private $translator;
    private $locale;
    private $disablePudenew;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];
        $this->disablePudenew = $options['disable_PUDENEW'];

        $builder
            ->add(
                'announce',
                CheckboxType::class,
                [
                    'label_attr' => $this->disablePudenew ? ['class' => 'pmk_disabled_checkbox'] : [],
                    'disabled' => $this->disablePudenew,
                    'required' => false,
                    'attr' => ['aria-label' => $this->translator->trans('Last Added (Announced)', [], null, $this->locale)],
                    'label' => $this->translator->trans('Last Added (Announced)', [], null, $this->locale),
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
                'i18n_title',
                TextI18nType::class,
                [
                    'attr' => ['aria-label' => $this->translator->trans('Title', [], null, $this->locale)],
                    'label' => $this->translator->trans('Title', [], null, $this->locale),
                ]
            )
            ->add(
                'i18n_subtitle',
                TextI18nType::class,
                [
                    'required' => false,
                    'attr' => ['aria-label' => $this->translator->trans('Subtitle', [], null, $this->locale)],
                    'label' => $this->translator->trans('Subtitle', [], null, $this->locale),
                ]
            )
            ->add(
                'i18n_description',
                TextareaI18nType::class,
                [
                    'required' => false,
                    'attr' => [
                        'style' => 'resize:vertical;',
                        'aria-label' => $this->translator->trans('Description', [], null, $this->locale),
                    ],
                    'label' => $this->translator->trans('Description', [], null, $this->locale),
                ]
            )
            ->add(
                'comments',
                TextareaType::class,
                [
                    'required' => false,
                    'attr' => ['style' => 'resize:vertical;'],
                    'label' => $this->translator->trans('Comments', [], null, $this->locale),
                ]
            )
            ->add(
                'i18n_keyword',
                TextI18nAdvanceType::class,
                [
                    'required' => false,
                    'attr' => [
                        'class' => 'series materialtags',
                        'aria-label' => $this->translator->trans('Keywords', [], null, $this->locale),
                    ],
                    'label' => $this->translator->trans('Keywords', [], null, $this->locale),
                ]
            )
            ->add(
                'series_type',
                null,
                [
                    'required' => false,
                    'attr' => ['aria-label' => $this->translator->trans('Channel', [], null, $this->locale)],
                    'label' => $this->translator->trans('Channel', [], null, $this->locale),
                ]
            )
            ->add(
                'series_style',
                null,
                [
                    'required' => false,
                    'attr' => ['aria-label' => $this->translator->trans('Series Style', [], null, $this->locale)],
                    'label' => $this->translator->trans('Series Style', [], null, $this->locale),
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
                'i18n_header',
                TextareaI18nType::class,
                [
                    'required' => false,
                    'attr' => [
                        'groupclass' => 'hidden-naked',
                        'style' => 'resize:vertical;',
                        'aria-label' => $this->translator->trans('Header Text', [], null, $this->locale),
                    ],
                    'label' => $this->translator->trans('Header Text', [], null, $this->locale),
                ]
            )
            ->add(
                'i18n_footer',
                TextareaI18nType::class,
                [
                    'required' => false,
                    'attr' => [
                        'groupclass' => 'hidden-naked',
                        'style' => 'resize:vertical;',
                        'aria-label' => $this->translator->trans('Footer Text', [], null, $this->locale),
                    ],
                    'label' => $this->translator->trans('Footer Text', [], null, $this->locale),
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
            ->add(
                'template',
                ChoiceType::class,
                [
                    'choices' => array_flip(
                        [
                            'date' => $this->translator->trans('date'),
                            'date_all' => $this->translator->trans('date_all'),
                            'date_subserial' => $this->translator->trans('date_subserial'),
                            'place_subserial' => $this->translator->trans('place_subserial'),
                            'subserial' => $this->translator->trans('subserial'),
                            'multisubserial' => $this->translator->trans('multisubserial'),
                        ]
                    ),
                    'empty_data' => null,
                    'mapped' => false,
                    'attr' => [
                        'groupclass' => 'hidden-naked',
                        'aria-label' => $this->translator->trans('Template', [], null, $this->locale),
                    ],
                    'required' => true,
                    'label' => $this->translator->trans('Template', [], null, $this->locale),
                ]
            )
            ->add(
                'sorting',
                ChoiceType::class,
                [
                    'choices' => array_flip(Series::$sortText),
                    'empty_data' => null,
                    'attr' => [
                        'groupclass' => 'hidden-naked',
                        'aria-label' => $this->translator->trans('Sorting', [], null, $this->locale),
                    ],
                    'required' => true,
                    'label' => $this->translator->trans('Sorting', [], null, $this->locale),
                ]
            )
        ;

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) {
                $series = $event->getData();
                $event->getForm()->get('template')->setData($series->getProperty('template'));
            }
        );

        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) {
                $template = $event->getForm()->get('template')->getData();
                $series = $event->getData();
                $series->setProperty('template', $template);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Pumukit\SchemaBundle\Document\Series',
            ]
        );

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
        $resolver->setRequired('disable_PUDENEW');
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_series';
    }
}
