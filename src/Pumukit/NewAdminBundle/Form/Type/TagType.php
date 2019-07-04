<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Pumukit\NewAdminBundle\Form\Type\Base\TextareaI18nType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TagType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $builder
            ->add(
                'metatag',
                CheckboxType::class,
                [
                    'required' => false,
                    'label_attr' => ['title' => $this->translator->trans('Not valid to tagged objets')],
                    'attr' => [
                        'aria-label' => $this->translator->trans('Metatag', [], null, $this->locale),
                    ],
                ]
            )
            ->add(
                'display',
                CheckboxType::class,
                [
                    'required' => false,
                    'label_attr' => ['title' => $this->translator->trans('Show tag on WebTV portal and edit categories on multimedia objects')],
                    'attr' => [
                        'aria-label' => $this->translator->trans('Display', [], null, $this->locale),
                    ],
                ]
            )
            ->add(
                'cod',
                TextType::class,
                [
                    'attr' => [
                        'aria-label' => $this->translator->trans('Cod', [], null, $this->locale),
                        'pattern' => '^\\w*$',
                        'oninvalid' => "setCustomValidity('The code can not have blank spaces neither special characters')",
                        'oninput' => "setCustomValidity('')",
                    ],
                    'label' => $this->translator->trans('Code', [], null, $this->locale),
                ]
            )
            ->add(
                'i18n_title',
                TextI18nType::class,
                [
                    'attr' => [
                        'aria-label' => $this->translator->trans('Title', [], null, $this->locale),
                    ],
                    'label' => $this->translator->trans('Name', [], null, $this->locale),
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
        ;

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $tag = $event->getData();

                $fields = $tag->getProperty('customfield');
                foreach (array_filter(preg_split('/[,\s]+/', $fields)) as $field) {
                    $auxField = explode(':', $field);
                    $formOptions = [
                        'mapped' => false,
                        'required' => false,
                        'attr' => [
                            'aria-label' => $this->translator->trans($auxField[0], [], null, $this->locale),
                        ],
                        'data' => $tag->getProperty($auxField[0]),
                    ];

                    try {
                        $type = $auxField[1] ?? TextType::class;
                        $event->getForm()->add($auxField[0], $type, $formOptions);
                    } catch (\InvalidArgumentException $e) {
                        $event->getForm()->add($auxField[0], TextType::class, $formOptions);
                    }
                }
            }
        );

        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) {
                $tag = $event->getData();

                $fields = $tag->getProperty('customfield');
                foreach (array_filter(preg_split('/[,\s]+/', $fields)) as $field) {
                    $auxField = explode(':', $field);
                    $data = $event->getForm()->get($auxField[0])->getData();
                    $tag->setProperty($auxField[0], $data);
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Pumukit\SchemaBundle\Document\Tag',
            ]
        );

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_tag';
    }
}
