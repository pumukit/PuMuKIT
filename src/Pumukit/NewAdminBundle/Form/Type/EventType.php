<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Pumukit\NewAdminBundle\Form\Type\Other\EventscheduleType;
use Symfony\Component\Translation\TranslatorInterface;

class EventType extends AbstractType
{
    private $translator;
    private $locale;

    public function __construct(TranslatorInterface $translator, $locale = 'en')
    {
        $this->translator = $translator;
        $this->locale = $locale;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text',
                  array('label' => $this->translator->trans('Event', array(), null, $this->locale)))
            ->add('i18n_description', 'textareai18n',
                  array(
                        'required' => false,
                        'attr' => array('style' => 'resize:vertical;'),
                        'label' => $this->translator->trans('Description', array(), null, $this->locale), ))
            ->add('place', 'text',
                  array('label' => $this->translator->trans('Location', array(), null, $this->locale)))
            ->add('live', null,
                  array('label' => $this->translator->trans('Channels', array(), null, $this->locale)))
            ->add('schedule', new EventscheduleType(),
                  array('label' => $this->translator->trans('Schedule', array(), null, $this->locale)))
            ->add('display', 'checkbox',
                  array('required' => false,
                        'label' => $this->translator->trans('Announce', array(), null, $this->locale), ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
        'data_class' => 'Pumukit\LiveBundle\Document\Event',
    ));
    }

    public function getName()
    {
        return 'pumukitnewadmin_event';
    }
}
