<?php

namespace Pumukit\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MaterialType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // TODO - check form is completed
        $builder
      ->add('i18n_name', 'texti18n', array('required' => false, 'attr' => array('style' => 'width: 420px'), 'label' => 'Name'))
          ->add('hide', 'checkbox', array('required' => false, 'label' => 'Hide'))
          ->add('mime_type', 'choice', array('choices' => array('TODO' => 'definir', 'mimetypes' => 'values'), 'label' => 'Type'))
      ->add('url', 'url', array('required' => false, 'attr' => array('style' => 'width: 420px'), 'label' => 'URL'))
      ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
        'data_class' => 'Pumukit\SchemaBundle\Document\Material',
    ));
    }

    public function getName()
    {
        return 'pumukitadmin_material';
    }
}
