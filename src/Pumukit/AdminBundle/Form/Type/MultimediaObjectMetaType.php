<?php

namespace Pumukit\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\AdminBundle\Form\Type\Other\Html5dateType;

class MultimediaObjectMetaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
      ->add('i18n_title', 'texti18n',
        array('attr' => array('style' => 'width: 420px'), 'label' => 'Title'))
      ->add('i18n_subtitle', 'texti18n',
        array('required' => false, 'attr' => array('style' => 'width: 420px'), 'label' => 'Subtitle'))
      ->add('i18n_keyword', 'texti18n',
        array('required' => false, 'attr' => array('style' => 'width: 420px'), 'label' => 'Keyword'))
      ->add('i18n_copyright', 'texti18n',
        array('required' => false, 'attr' => array('style' => 'width: 420px'), 'label' => 'Copyright'))
      // TODO with tags ->add('genre', null, array('required' => false, 'label' => 'Genre'))
      ->add('public_date', new Html5dateType(),
        array('attr' => array('style' => 'width: 420px'), 'data_class' => 'DateTime', 'label' => 'Public Date'))
      ->add('record_date', new Html5dateType(),
        array('attr' => array('style' => 'width: 420px'), 'data_class' => 'DateTime', 'label' => 'Record Date'))
      ->add('i18n_description', 'textareai18n',
        array('required' => false, 'attr' => array('style' => 'width: 420px'), 'label' => 'Description'))
      /* TODO ->add('subserie', 'checkbox',
         array('required' => false), 'label' => 'Subserie:'))*/
      ->add('i18n_line2', 'textareai18n',
        array('required' => false, 'attr' => array('style' => 'width: 420px'), 'label' => 'Headline'));
    /* TODO ->add('i18n_subserie_title', 'textareai18n',
       array('required' => false, 'attr' => array('style' => 'width: 420px'), 'label' => 'Subserie Title:')); */
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
        'data_class' => 'Pumukit\SchemaBundle\Document\MultimediaObject',
    ));
    }

    public function getName()
    {
        return 'pumukitadmin_mms_meta';
    }
}
