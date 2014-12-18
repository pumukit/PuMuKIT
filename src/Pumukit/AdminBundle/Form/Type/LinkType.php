<?php

namespace Pumukit\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class LinkType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
      ->add('i18n_name', 'texti18n', array('required' => false, 'attr' => array('style' => 'width: 420px'), 'label' => 'Name'))
      ->add('url', 'url', array('required' => false, 'attr' => array('style' => 'width: 420px'), 'label' => 'URL'))
      ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
        'data_class' => 'Pumukit\SchemaBundle\Document\Link',
    ));
    }

    public function getName()
    {
        return 'pumukitadmin_link';
    }
}
