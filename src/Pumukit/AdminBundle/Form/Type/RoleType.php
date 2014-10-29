<?php

namespace Pumukit\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Pumukit\SchemaBundle\Document\Role;

class RoleType extends AbstractType
{

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('display', 'checkbox')
      ->add('cod', 'text',
	    array('attr' => array('style' => 'width: 420px'), 'label' => 'Código'))
      ->add('xml', 'text',
	    array('attr' => array('style' => 'width: 420px'), 'label' => 'XML'))
      ->add('i18n_name', 'texti18n', 
	    array('attr' => array('style' => 'width: 420px'), 'label' => 'Nombre'))
      ->add('i18n_text', 'textareai18n', 
	    array('required'=>false, 'attr' => array('style' => 'width: 420px'), 'label' => 'Texto'));
  }
  

  public function setDefaultOptions(OptionsResolverInterface $resolver)
  {
    $resolver->setDefaults(array(
        'data_class' => 'Pumukit\SchemaBundle\Document\Role'
    ));
  }

  public function getName()
  {
    return 'pumukitadmin_role';
  }
}