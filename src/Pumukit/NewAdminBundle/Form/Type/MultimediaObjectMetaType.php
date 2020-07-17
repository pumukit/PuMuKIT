<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Pumukit\NewAdminBundle\Form\Type\Base\LicenseType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextareaI18nType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nAdvanceType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nType;
use Pumukit\NewAdminBundle\Form\Type\Other\Html5dateType;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Security\Permission;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MultimediaObjectMetaType extends AbstractType
{
    private $translator;
    private $locale;
    private $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $builder
            ->add(
                'i18n_title',
                TextI18nType::class,
                [
                    'required' => false,
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
                    'attr' => ['style' => 'resize:vertical;',
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
        ;

        if ($this->authorizationChecker->isGranted(Permission::ACCESS_MULTIMEDIA_META_KEYWORDS)) {
            $builder->add(
                'i18n_keyword',
                TextI18nAdvanceType::class,
                [
                    'required' => false,
                    'attr' => [
                        'class' => 'mmobj materialtags',
                        'aria-label' => $this->translator->trans('Keywords', [], null, $this->locale),
                    ],
                    'label' => $this->translator->trans('Keywords', [], null, $this->locale),
                ]
            );
        }

        if ($this->authorizationChecker->isGranted(Permission::ACCESS_MULTIMEDIA_META_COPYRIGHT)) {
            $builder->add(
                'copyright',
                TextType::class,
                [
                    'required' => false,
                    'attr' => ['aria-label' => $this->translator->trans('Copyright', [], null, $this->locale)],
                    'label' => $this->translator->trans('Copyright', [], null, $this->locale),
                ]
            );
        }
        if ($this->authorizationChecker->isGranted(Permission::ACCESS_MULTIMEDIA_META_LICENSE)) {
            $builder->add(
                'license',
                LicenseType::class,
                [
                    'required' => false,
                    'attr' => ['aria-label' => $this->translator->trans('License', [], null, $this->locale)],
                    'label' => $this->translator->trans('License', [], null, $this->locale),
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
        ;

        if ($this->authorizationChecker->isGranted(Permission::ACCESS_MULTIMEDIA_META_HEADLINE)) {
            $builder->add(
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
            );
        }

        if ($this->authorizationChecker->isGranted(Permission::ACCESS_MULTIMEDIA_META_SUBSERIE)) {
            $builder->add(
                'subseries',
                CheckboxType::class,
                [
                    'mapped' => false,
                    'required' => false,
                    'attr' => [
                        'groupclass' => 'hidden-naked',
                        'aria-label' => $this->translator->trans('Subseries', [], null, $this->locale),
                    ],
                    'label' => $this->translator->trans('Subseries', [], null, $this->locale),
                ]
            )
                ->add(
                    'subseriestitle',
                    TextI18nType::class,
                    [
                        'mapped' => false,
                        'required' => false,
                        'attr' => [
                            'groupclass' => 'hidden-naked',
                            'aria-label' => $this->translator->trans('Subseries', [], null, $this->locale),
                        ],
                        'label' => $this->translator->trans('Subseries', [], null, $this->locale),
                    ]
                )
            ;
        }

        if ($this->authorizationChecker->isGranted(Permission::ACCESS_MULTIMEDIA_META_SUBSERIE)) {
            $builder->addEventListener(
                FormEvents::POST_SET_DATA,
                static function (FormEvent $event) {
                    $multimediaObject = $event->getData();
                    $event->getForm()->get('subseries')->setData($multimediaObject->getProperty('subseries'));
                    $event->getForm()->get('subseriestitle')->setData($multimediaObject->getProperty('subseriestitle'));
                }
            );

            $builder->addEventListener(
                FormEvents::SUBMIT,
                static function (FormEvent $event) {
                    $subseries = $event->getForm()->get('subseries')->getData();
                    $subseriestitle = $event->getForm()->get('subseriestitle')->getData();
                    $multimediaObject = $event->getData();
                    $multimediaObject->setProperty('subseries', $subseries);
                    $multimediaObject->setProperty('subseriestitle', $subseriestitle);
                }
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => MultimediaObject::class,
            ]
        );

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix(): string
    {
        return 'pumukitnewadmin_mms_meta';
    }
}
