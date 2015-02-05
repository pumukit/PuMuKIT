<?php
namespace Pumukit\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Pumukit\AdminBundle\Form\Type\Other\TrackresolutionType;
use Pumukit\AdminBundle\Form\Type\Other\TrackdurationType;

class TrackUpdateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
          ->add('i18n_description', 'texti18n', array(
                                                      'required' => true, 
                                                      'attr' => array('style' => 'width: 420px'), 
                                                      'label' => 'Description'))
          ->add('hide', 'checkbox', array('required' => false, 'label' => 'Hide'))
          ->add('language', 'language', array(
                                              'required' => false,
                                              'label' => 'Language'
                                              ))
          ->add('durationinminutesandseconds', new TrackdurationType(), array(
                                                                              'required' => true,
                                                                              'read_only' => true,
                                                                              'label' => 'Duration'))
          ->add('resolution', new TrackresolutionType(), array(
                                                               'required' => true,
                                                               'read_only' => true,
                                                               'label' => 'Resolution'))
          ->add('size', 'integer', array(
                                         'required' => true,
                                         'read_only' => true,
                                         'attr' => array('style' => 'width: 420px'), 
                                         'label' => 'Size'))
          ->add('path', 'text', array(
                                         'required' => true,
                                         'read_only' => true,
                                         'attr' => array('style' => 'width: 420px'), 
                                         'label' => 'File'))

          ->add('url', 'text', array(
                                         'required' => true,
                                         'read_only' => true,
                                         'attr' => array('style' => 'width: 420px'), 
                                         'label' => 'URL'))
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
        return 'pumukitadmin_track_update';
    }
}