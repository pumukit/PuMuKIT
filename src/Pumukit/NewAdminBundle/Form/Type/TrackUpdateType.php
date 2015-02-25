<?php
namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Pumukit\NewAdminBundle\Form\Type\Other\TrackresolutionType;
use Pumukit\NewAdminBundle\Form\Type\Other\TrackdurationType;

class TrackUpdateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
          ->add('i18n_description', 'texti18n', array(
                                                      'required' => true, 
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
                                         'label' => 'Size'))
          ->add('path', 'text', array(
                                         'required' => true,
                                         'read_only' => true,
                                         'label' => 'File'))

          ->add('url', 'text', array(
                                         'required' => true,
                                         'read_only' => true,
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
        return 'pumukitnewadmin_track_update';
    }
}