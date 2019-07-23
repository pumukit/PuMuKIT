<?php

namespace Pumukit\OpencastBundle\Form\Type;

use Pumukit\NewAdminBundle\Form\Type\Base\CustomLanguageType;
use Pumukit\NewAdminBundle\Form\Type\Other\TrackdurationType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MultimediaObjectType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $invertText = $this->translator->trans('Invert', [], null, $this->locale).' (CAMERA-SCREEN)';

        $builder
            ->add(
                'opencastinvert',
                CheckboxType::class,
                [
                    'required' => false,
                    'mapped' => false,
                    'attr' => ['aria-label' => $invertText],
                    'label' => $invertText,
                ]
            )
            ->add(
                'opencastlanguage',
                CustomLanguageType::class,
                [
                    'required' => true,
                    'mapped' => false,
                    'attr' => ['aria-label' => $this->translator->trans('Language', [], null, $this->locale)],
                    'label' => $this->translator->trans('Language', [], null, $this->locale),
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
        ;

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) {
                $multimediaObject = $event->getData();
                $event->getForm()->get('opencastinvert')->setData($multimediaObject->getProperty('opencastinvert'));
                $event->getForm()->get('opencastlanguage')->setData($multimediaObject->getProperty('opencastlanguage'));
            }
        );

        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) {
                $opencastInvert = $event->getForm()->get('opencastinvert')->getData();
                $opencastLanguage = strtolower($event->getForm()->get('opencastlanguage')->getData());
                $multimediaObject = $event->getData();
                $multimediaObject->setProperty('opencastinvert', $opencastInvert);
                $multimediaObject->setProperty('opencastlanguage', $opencastLanguage);
            }
        );
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
        return 'pumukit_opencast_multimedia_object';
    }
}
