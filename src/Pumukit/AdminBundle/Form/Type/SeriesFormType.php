<?php

namespace Pumukit\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\AdminBundle\Form\Type\Other\Html5dateType;

class SeriesFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
      ->add('announce', 'checkbox',
        array('required' => false, 'label' => 'New'))
      ->add('i18n_title', 'texti18n',
        array('attr' => array('style' => 'width: 420px'), 'label' => 'Title'))
      ->add('i18n_keyword', 'texti18n',
        array('required' => false, 'attr' => array('style' => 'width: 420px'), 'label' => 'Keyword'))
      ->add('i18n_copyright', 'texti18n',
        array('required' => false, 'attr' => array('style' => 'width: 420px'), 'label' => 'Copyright'))
      ->add('series_type', null, array('required' => false, 'label' => 'Channel'))
      ->add('public_date', new Html5dateType(),
        array('attr' => array('style' => 'width: 420px'), 'data_class' => 'DateTime', 'label' => 'Public Date'))
      ->add('i18n_description', 'textareai18n',
        array('required' => false, 'attr' => array('style' => 'width: 420px'), 'label' => 'Description'))
      ->add('i18n_header', 'textareai18n',
        array('required' => false, 'attr' => array('style' => 'width: 420px'), 'label' => 'Header Text:'))
      ->add('i18n_footer', 'textareai18n',
        array('required' => false, 'attr' => array('style' => 'width: 420px'), 'label' => 'Footer Text:'))
      ->add('i18n_line2', 'textareai18n',
        array('required' => false, 'attr' => array('style' => 'width: 420px'), 'label' => 'Headline'));
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
