<?php

namespace Pumukit\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Pumukit\AdminBundle\Form\Type\Other\EventscheduleType;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
      ->add('i18n_name', 'texti18n',
        array('attr' => array('style' => 'width: 420px'), 'label' => 'Event'))
      ->add('place', 'text',
        array('attr' => array('style' => 'width: 420px'), 'label' => 'Place'))
      ->add('direct', null, array('label' => 'Channels'))
      ->add('schedule', new EventscheduleType(),
    array('attr' => array('style' => 'width: 420px'), 'label' => 'Schedule'))
      ->add('display', 'checkbox', array('required' => false));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
        'data_class' => 'Pumukit\DirectBundle\Document\Event',
    ));
    }

    public function getName()
    {
        return 'pumukitadmin_event';
    }
}
