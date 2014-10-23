<?php

namespace Pumukit\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class EventType extends AbstractType
{

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('name')
      ->add('place')
      ->add('date', 'date')
      ->add('duration', 'number')
      ->add('display', 'checkbox', array('required'=>false))
      ->add('create_serial', 'checkbox', array('required'=>false))
      ->add('save', 'submit');
  }
  

  public function setDefaultOptions(OptionsResolverInterface $resolver)
  {
    $resolver->setDefaults(array(
        'data_class' => 'Pumukit\DirectBundle\Document\Event'
    ));
  }

  public function getName()
  {
    return 'pumukitadmin_event';
  }
}