<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\NewAdminBundle\Form\Type\Other\Html5dateType;

class MultimediaObjectMetaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('i18n_title', 'texti18n', array('label' => 'Title'))
            ->add('i18n_subtitle', 'texti18n', array('required' => false, 'label' => 'Subtitle'))
            ->add('i18n_keyword', 'texti18n',array('required' => false, 'label' => 'Keyword'))
            ->add('i18n_copyright', 'texti18n', array('required' => false, 'label' => 'Copyright'))
            ->add('public_date', new Html5dateType(), array('data_class' => 'DateTime', 'label' => 'Public Date'))
            ->add('record_date', new Html5dateType(), array('data_class' => 'DateTime', 'label' => 'Record Date'))
            ->add('i18n_description', 'textareai18n', array('required' => false, 'label' => 'Description'))
            ->add('i18n_line2', 'textareai18n', array('required' => false, 'label' => 'Headline'));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pumukit\SchemaBundle\Document\MultimediaObject',
        ));
    }

    public function getName()
    {
        return 'pumukitnewadmin_mms_meta';
    }
}
