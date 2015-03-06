<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Pumukit\LiveBundle\Document\Live;
use Pumukit\NewAdminBundle\Form\Type\Other\LivequalitiesType;
use Pumukit\NewAdminBundle\Form\Type\Other\LiveresolutionType;

class LiveType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
      ->add('i18n_name', 'texti18n', array('label' => 'Name'))
      ->add('i18n_description', 'textareai18n',
        array('required' => false, 'label' => 'Description'))
      ->add('url', 'url', array('label' => 'URL'))
      ->add('broadcasting', 'choice',
        array('choices'   => array('0' => 'On hold', '1' => 'Live Broadcasting'),
          'label' => 'Status', ))
      ->add('live_type', 'choice',
        array('choices'   => array(Live::LIVE_TYPE_FMS => 'FMS', Live::LIVE_TYPE_WMS => 'WMS'),
          'label' => 'Tecnology', ))
      ->add('resolution', new LiveresolutionType(),
        array('label' => 'Resolution', 'required' => false))
      ->add('qualities', new LivequalitiesType(),
        array('label' => 'Qualities', 'required' => false))
      ->add('ip_source', 'text',
        array('required' => false))
      ->add('source_name', 'text',
        array('label' => 'STREAM'))
      ->add('index_play', 'checkbox', array('required' => false));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
        'data_class' => 'Pumukit\LiveBundle\Document\Live',
    ));
    }

    public function getName()
    {
        return 'pumukitnewadmin_live';
    }
}
