<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Pumukit\NewAdminBundle\Form\Type\Other\EventscheduleType;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
      ->add('name', 'text', array('label' => 'Event'))
      ->add('place', 'text', array('label' => 'Place'))
      ->add('live', null, array('label' => 'Channels'))
      ->add('schedule', new EventscheduleType(), array('label' => 'Schedule'))
      ->add('display', 'checkbox', array('required' => false));
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
