<?php
namespace Pumukit\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TrackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /*
        $langs = $options['languages'];
        $langs = array_combine($langs, $langs);
        foreach ($langs as &$lang) {
          $lang = \Locale::getDisplayName($lang);
        }
        */

        // TODO - check form is completed
        $builder
          ->add('i18n_description', 'texti18n', array('required' => true, 'attr' => array('style' => 'width: 420px'), 'label' => 'Description'))
          //->add('profile', 'radio', array('required' => true, 'label' => 'Profile'))
          ->add('priority', 'choice', array(
                                            'choices' => array(
                                                               '1' => 'Low-Priority', 
                                                               '2' => 'Normal-Priority',
                                                               '3' => 'High-Priority',
                                                               ),
                                            'expanded' => true,
                                            'multiple' => false,
                                            'required' => true,
                                            'label' => 'Priority'))
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