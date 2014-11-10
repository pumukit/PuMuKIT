<?php

namespace Pumukit\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BroadcastType extends AbstractType
{

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('i18n_name', 'texti18n',
	    array('attr' => array('style' => 'width: 420px'), 'label' => 'Nombre'));
  }
  

  public function setDefaultOptions(OptionsResolverInterface $resolver)
  {
    $resolver->setDefaults(array(
        'data_class' => 'Pumukit\AdminBundle\Document\Broadcast'
    ));
  }

  public function getName()
  {
    return 'pumukitadmin_broadcast';
  }
}