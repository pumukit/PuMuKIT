<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Pumukit\NewAdminBundle\Form\Type\Other\EventscheduleType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextareaI18nType;

class EventType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $builder
            ->add('name', TextType::class,
                  array(
                      'attr' => array('aria-label' => $this->translator->trans('Event', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Event', array(), null, $this->locale), ))
            ->add('i18n_description', TextareaI18nType::class,
                  array(
                      'required' => false,
                      'attr' => array('style' => 'resize:vertical;', 'aria-label' => $this->translator->trans('Event', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Description', array(), null, $this->locale), ))
            ->add('place', TextType::class,
                  array(
                      'attr' => array('aria-label' => $this->translator->trans('Location', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Location', array(), null, $this->locale), ))
            ->add('live', null,
                  array(
                      'required' => false,
                      'attr' => array('aria-label' => $this->translator->trans('Channels', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Channels', array(), null, $this->locale), ))
            ->add('schedule', new EventscheduleType(),
                  array(
                      'attr' => array('aria-label' => $this->translator->trans('Schedule', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Schedule', array(), null, $this->locale), ))
            ->add('display', CheckboxType::class,
                  array('required' => false,
                        'attr' => array('aria-label' => $this->translator->trans('Announce', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Announce', array(), null, $this->locale), ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pumukit\LiveBundle\Document\Event',
        ));

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_event';
    }
}
