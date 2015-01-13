<?php
namespace Pumukit\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TrackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
          ->add('i18n_description', 'texti18n', array('required' => true, 'attr' => array('style' => 'width: 420px'), 'label' => 'Description'))
          ->add('language', 'language', array(
                                              'required' => true,
                                              'label' => 'Language'
                                              ))
          ;
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
                                     'data_class' => 'Pumukit\SchemaBundle\Document\Track',
                                     ));
    }
    
    public function getName()
    {
      return 'pumukitadmin_track';
    }
}