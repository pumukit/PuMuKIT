<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Pumukit\NewAdminBundle\Form\Type\Base\TextareaI18nType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nAdvanceType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nType;
use Pumukit\NewAdminBundle\Form\Type\Other\Html5dateType;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Security\Permission;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SeriesType extends AbstractType
{
    private $translator;
    private $locale;
    private $disablePudenew;
    private $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];
        $this->disablePudenew = $options['disable_PUDENEW'];

        if($this->authorizationChecker->isGranted(Permission::ACCESS_SERIES_META_LAST_ANNOUNCES)) {
            $builder
                ->add(
                    'announce',
                    CheckboxType::class,
                    [
                        'label_attr' => $this->disablePudenew ? ['class' => 'pmk_disabled_checkbox'] : [],
                        'disabled'   => $this->disablePudenew,
                        'required'   => false,
                        'attr'       => [
                            'aria-label' => $this->translator->trans(
                                'Last Added (Announced)',
                                [],
                                null,
                                $this->locale
                            )
                        ],
                        'label'      => $this->translator->trans('Last Added (Announced)', [], null, $this->locale),
                    ]
                );
        }

        if($this->authorizationChecker->isGranted(Permission::ACCESS_SERIES_META_DISPLAY)) {
            $builder->add(
                'hide',
                CheckboxType::class,
                [
                    'required' => false,
                    'attr'     => ['aria-label' => $this->translator->trans('Hide', [], null, $this->locale)],
                    'label'    => $this->translator->trans('Hide', [], null, $this->locale),
                ]
            );
        }

            $builder->add(
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
            );

        if($this->authorizationChecker->isGranted(Permission::ACCESS_SERIES_META_KEYWORDS)) {
            $builder->add(
                'i18n_keyword',
                TextI18nAdvanceType::class,
                [
                    'required' => false,
                    'attr'     => [
                        'class'      => 'series materialtags',
                        'aria-label' => $this->translator->trans('Keywords', [], null, $this->locale),
                    ],
                    'label'    => $this->translator->trans('Keywords', [], null, $this->locale),
                ]
            );
        }

        if($this->authorizationChecker->isGranted(Permission::ACCESS_SERIES_META_CHANNELS)) {
            $builder->add(
                'series_type',
                null,
                [
                    'required' => false,
                    'attr'     => ['aria-label' => $this->translator->trans('Channel', [], null, $this->locale)],
                    'label'    => $this->translator->trans('Channel', [], null, $this->locale),
                ]
            );
        }

        if($this->authorizationChecker->isGranted(Permission::ACCESS_SERIES_META_STYLE)) {
            $builder->add(
                'series_style',
                null,
                [
                    'required' => false,
                    'attr'     => ['aria-label' => $this->translator->trans('Series Style', [], null, $this->locale)],
                    'label'    => $this->translator->trans('Series Style', [], null, $this->locale),
                ]
            );
        }

        $builder->add(
                'public_date',
                Html5dateType::class,
                [
                    'data_class' => 'DateTime',
                    'attr' => ['aria-label' => $this->translator->trans('Publication Date', [], null, $this->locale)],
                    'label' => $this->translator->trans('Publication Date', [], null, $this->locale),
                ]
            );

        if($this->authorizationChecker->isGranted(Permission::ACCESS_SERIES_META_HTML_CONFIGURATION)) {
            $builder->add(
                'i18n_header',
                TextareaI18nType::class,
                [
                    'required' => false,
                    'attr'     => [
                        'groupclass' => 'hidden-naked',
                        'style'      => 'resize:vertical;',
                        'aria-label' => $this->translator->trans('Header Text', [], null, $this->locale),
                    ],
                    'label'    => $this->translator->trans('Header Text', [], null, $this->locale),
                ]
            )
                ->add(
                    'i18n_footer',
                    TextareaI18nType::class,
                    [
                        'required' => false,
                        'attr'     => [
                            'groupclass' => 'hidden-naked',
                            'style'      => 'resize:vertical;',
                            'aria-label' => $this->translator->trans('Footer Text', [], null, $this->locale),
                        ],
                        'label'    => $this->translator->trans('Footer Text', [], null, $this->locale),
                    ]
                );
        }

        if($this->authorizationChecker->isGranted(Permission::ACCESS_SERIES_META_HEADLINE)) {
            $builder->add(
                'i18n_line2',
                TextareaI18nType::class,
                [
                    'required' => false,
                    'attr'     => [
                        'groupclass' => 'hidden-naked',
                        'style'      => 'resize:vertical;',
                        'aria-label' => $this->translator->trans('Headline', [], null, $this->locale),
                    ],
                    'label'    => $this->translator->trans('Headline', [], null, $this->locale),
                ]
            );
        }

        if($this->authorizationChecker->isGranted(Permission::ACCESS_SERIES_META_TEMPLATE)) {
            $builder->add(
                'template',
                ChoiceType::class,
                [
                    'choices'    => array_flip(
                        [
                            'date'            => $this->translator->trans('date'),
                            'date_all'        => $this->translator->trans('date_all'),
                            'date_subserial'  => $this->translator->trans('date_subserial'),
                            'place_subserial' => $this->translator->trans('place_subserial'),
                            'subserial'       => $this->translator->trans('subserial'),
                            'multisubserial'  => $this->translator->trans('multisubserial'),
                        ]
                    ),
                    'empty_data' => null,
                    'mapped'     => false,
                    'attr'       => [
                        'groupclass' => 'hidden-naked',
                        'aria-label' => $this->translator->trans('Template', [], null, $this->locale),
                    ],
                    'required'   => true,
                    'label'      => $this->translator->trans('Template', [], null, $this->locale),
                ]
            );
        }

        $builder->add(
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

        if($this->authorizationChecker->isGranted(Permission::ACCESS_SERIES_META_TEMPLATE)) {
            $builder->addEventListener(
                FormEvents::POST_SET_DATA,
                static function (FormEvent $event) {
                    $series = $event->getData();
                    $event->getForm()->get('template')->setData($series->getProperty('template'));
                }
            );

            $builder->addEventListener(
                FormEvents::SUBMIT,
                static function (FormEvent $event) {
                    $template = $event->getForm()->get('template')->getData();
                    $series = $event->getData();
                    $series->setProperty('template', $template);
                }
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Series::class
            ]
        );

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
        $resolver->setRequired('disable_PUDENEW');
    }

    public function getBlockPrefix(): string
    {
        return 'pumukitnewadmin_series';
    }
}
