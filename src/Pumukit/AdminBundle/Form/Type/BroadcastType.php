<?php

namespace Pumukit\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Pumukit\SchemaBundle\Document\Broadcast;

class BroadcastType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
      ->add('i18n_name', 'texti18n',
        array('attr' => array('style' => 'width: 420px'), 'label' => 'Name'))
      ->add('broadcast_type_id', 'choice',
        array('choices' => array(
                       Broadcast::BROADCAST_TYPE_PUB => Broadcast::BROADCAST_TYPE_PUB,
                       Broadcast::BROADCAST_TYPE_PRI => Broadcast::BROADCAST_TYPE_PRI,
                       Broadcast::BROADCAST_TYPE_COR => Broadcast::BROADCAST_TYPE_COR, ),
          'label' => 'Type', ))
      ->add('passwd', 'password',
        array('attr' => array('style' => 'width: 420px'), 'label' => 'Passwd'))
      ->add('i18n_description', 'textareai18n',
        array('attr' => array('style' => 'width: 420px'), 'label' => 'Description'));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
        'data_class' => 'Pumukit\SchemaBundle\Document\Broadcast',
    ));
    }

    public function getName()
    {
        return 'pumukitadmin_broadcast';
    }
}
