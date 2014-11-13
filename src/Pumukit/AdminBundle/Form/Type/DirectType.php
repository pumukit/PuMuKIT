<?php

namespace Pumukit\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Pumukit\DirectBundle\Document\Direct;
use Pumukit\AdminBundle\Form\Type\Other\DirectqualitiesType;
use Pumukit\AdminBundle\Form\Type\Other\DirectresolutionType;

class DirectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
      ->add('i18n_name', 'texti18n',
        array('attr' => array('style' => 'width: 420px'), 'label' => 'Nombre'))
      ->add('i18n_description', 'textareai18n',
        array('required' => false, 'attr' => array('style' => 'width: 420px'), 'label' => 'Description'))
      ->add('url', 'url',
        array('attr' => array('style' => 'width: 420px'), 'label' => 'Url'))
      ->add('broadcasting', 'choice',
        array('choices'   => array('0'   => 'Espera', '1' => 'Emitiendo en Directo'),
          'label' => 'Estado', ))
      ->add('direct_type', 'choice',
        array('choices'   => array(Direct::DIRECT_TYPE_FMS => 'FMS', Direct::DIRECT_TYPE_WMS => 'WMS'),
          'label' => 'Tecnología', ))
      ->add('resolution', new DirectresolutionType(),
        array('label' => 'Resolución', 'required' => false))
      ->add('qualities', new DirectqualitiesType(),
        array('label' => 'Calidades', 'required' => false))
      ->add('ip_source', 'text',
        array('required' => false))
      ->add('source_name', 'text',
        array('label' => 'STREAM'))
      ->add('index_play', 'checkbox', array('required' => false));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
        'data_class' => 'Pumukit\DirectBundle\Document\Direct',
    ));
    }

    public function getName()
    {
        return 'pumukitadmin_direct';
    }
}
