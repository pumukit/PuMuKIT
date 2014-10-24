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
      ->add('i18n_name', 'texti18n', 
	    array('attr' => array('style' => 'width: 420px'), 'label' => 'Nombre'))
      ->add('i18n_description', 'textareai18n', 
	    array('required'=>false, 'attr' => array('style' => 'width: 420px'), 'label' => 'Description'))
      ->add('url', 'url',
	    array('attr' => array('style' => 'width: 420px'), 'label' => 'Url'))
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