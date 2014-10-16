<?php

namespace Pumukit\AdminBundle\Form\Type;

use FOS\UserBundle\Form\Type\ProfileFormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class UserType extends ProfileFormType
{

  /** @var string */
  private $dataClass;

  /**                                                                                                                                
   * {@inheritdoc}                                                                                                                   
   */
  public function __construct($dataClass)
  {
      $this->dataClass = $dataClass;
  }


  public function buildForm(FormBuilderInterface $builder, array $options)
  {
      $this->buildUserForm($builder, $options);
  }
  

  public function setDefaultOptions(OptionsResolverInterface $resolver)
  {
    $resolver->setDefaults(array(
        'data_class' => $this->dataClass
    ));
  }

  public function getName()
  {
    return 'pumukitadmin_user';
  }
}