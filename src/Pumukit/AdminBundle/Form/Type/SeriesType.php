<?php

namespace Pumukit\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Pumukit\SeriesBundle\Document\Series;

class SeriesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
      ->add('i18n_keyword', 'texti18n',
        array('attr' => array('style' => 'width: 420px'), 'label' => 'Keyword'))
      ->add('i18n_copyright', 'texti18n',
        array('required' => false, 'attr' => array('style' => 'width: 420px'), 'label' => 'Copyright'))
      /*->add('broadcast_type_id', 'choice',
        array('choices' => array(
                       Broadcast::BROADCAST_TYPE_PUB => Broadcast::BROADCAST_TYPE_PUB,
                       Broadcast::BROADCAST_TYPE_PRI => Broadcast::BROADCAST_TYPE_PRI,
                       Broadcast::BROADCAST_TYPE_COR => Broadcast::BROADCAST_TYPE_COR),
           'label' => 'Broadcast'))*/
      /*->add('genre', 'choice',
        array('attr' => array('style' => 'width: 420px'), 'label' => 'Genre',
                  'choices' => array()))*/
      /*->add('public_date', new EventscheduleType(),
    array('attr' => array('style' => 'width: 420px'), 'label' => 'Published Date'))*/
      /*->add('recorded_date', new EventscheduleType(),
    array('attr' => array('style' => 'width: 420px'), 'label' => 'Recorded Date'))*/
      ->add('i18n_description', 'textareai18n',
        array('required' => false, 'attr' => array('style' => 'width: 420px'), 'label' => 'Description'));
      /*->add('subseries', 'checkbox', array('required' => false, 'label' => 'Estado'))*/
      /*->add('i18n_subseries_title', 'textareai18n', array('required'=>false, 'attr' => array('style' => 'width: 420px'), 'label' => 'Subseries title'));*/
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
        'data_class' => 'Pumukit\SchemaBundle\Document\Series',
    ));
    }

    public function getName()
    {
        return 'pumukitadmin_series';
    }
}
