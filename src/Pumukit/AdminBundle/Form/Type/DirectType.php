<?php

namespace Pumukit\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class DirectType extends AbstractType
{

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('name', 'text')
      ->add('description', 'textarea', array('required'=>false))
      ->add('url')
      ->add('direct_type_id', 'number', array('required'=>false))
      ->add('resolution_width', 'number', array('required'=>false))
      ->add('resolution_height', 'number', array('required'=>false))
      ->add('qualities', 'text', array('required'=>false))
      ->add('ip_source', 'text', array('required'=>false))
      ->add('source_name')
      ->add('index_play', 'checkbox', array('required'=>false))
      ->add('broadcasting', 'checkbox', array('required'=>false));
  }
  

  public function setDefaultOptions(OptionsResolverInterface $resolver)
  {
    $resolver->setDefaults(array(
        'data_class' => 'Pumukit\DirectBundle\Document\Direct'
    ));
  }

  public function getName()
  {
    return 'pumukitadmin_direct';
  }
}