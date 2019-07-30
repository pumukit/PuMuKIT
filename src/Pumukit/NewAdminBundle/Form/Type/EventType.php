<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Pumukit\NewAdminBundle\Form\Type\Base\TextareaI18nType;
use Pumukit\NewAdminBundle\Form\Type\Other\EventscheduleType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'attr' => [
                        'aria-label' => $this->translator->trans('Event', [], null, $this->locale),
                    ],
                    'label' => $this->translator->trans('Event', [], null, $this->locale),
                ]
            )
            ->add(
                'i18n_description',
                TextareaI18nType::class,
                [
                    'required' => false,
                    'attr' => [
                        'style' => 'resize:vertical;',
                        'aria-label' => $this->translator->trans('Event', [], null, $this->locale),
                    ],
                    'label' => $this->translator->trans('Description', [], null, $this->locale),
                ]
            )
            ->add(
                'place',
                TextType::class,
                [
                    'attr' => [
                        'aria-label' => $this->translator->trans('Location', [], null, $this->locale),
                    ],
                    'label' => $this->translator->trans('Location', [], null, $this->locale),
                ]
            )
            ->add(
                'live',
                null,
                [
                    'required' => false,
                    'attr' => [
                        'aria-label' => $this->translator->trans('Channels', [], null, $this->locale),
                    ],
                    'label' => $this->translator->trans('Channels', [], null, $this->locale),
                ]
            )
            ->add(
                'schedule',
                EventscheduleType::class,
                [
                    'attr' => [
                        'aria-label' => $this->translator->trans('Schedule', [], null, $this->locale),
                    ],
                    'label' => $this->translator->trans('Schedule', [], null, $this->locale),
                ]
            )
            ->add(
                'display',
                CheckboxType::class,
                [
                    'required' => false,
                    'attr' => [
                        'aria-label' => $this->translator->trans('Announce', [], null, $this->locale),
                    ],
                    'label' => $this->translator->trans('Announce', [], null, $this->locale),
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Pumukit\SchemaBundle\Document\Event',
            ]
        );

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_event';
    }
}
