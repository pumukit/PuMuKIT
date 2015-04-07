<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class MultimediaObjectPubType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
      ->add('status', 'choice',
        array('choices' => array(
                  MultimediaObject::STATUS_PUBLISHED => 'Published',
                  MultimediaObject::STATUS_BLOQ => 'Blocked',
                  MultimediaObject::STATUS_HIDE => 'Hidden'
            ),
          'label' => 'Status', ))
      ->add('broadcast', null, array('label' => 'Broadcast'));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
        'data_class' => 'Pumukit\SchemaBundle\Document\MultimediaObject',
    ));
    }

    public function getName()
    {
        return 'pumukitnewadmin_mms_pub';
    }
}
