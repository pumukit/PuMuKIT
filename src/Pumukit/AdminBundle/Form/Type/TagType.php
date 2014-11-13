<?php

namespace Pumukit\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TagType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
      ->add('metatag', 'checkbox', array('required' => false))
      ->add('display', 'checkbox', array('required' => false))
      ->add('cod', 'text', array(
          'attr' => array(
          'pattern' => "^\w*$",
          'oninvalid' => "setCustomValidity('The code can not have blank spaces neither special characters')",
          'oninput' => "setCustomValidity('')",
              'style' => 'width: 420px', ),
          'label' => 'Cod', ))
      ->add('i18n_title', 'texti18n',
        array('attr' => array('style' => 'width: 420px'), 'label' => 'Titulo'))
      ->add('i18n_description', 'textareai18n',
        array('required' => false, 'attr' => array('style' => 'width: 420px'), 'label' => 'Description'))
      ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
        'data_class' => 'Pumukit\SchemaBundle\Document\Tag',
    ));
    }

    public function getName()
    {
        return 'pumukitadmin_tag';
    }
}
