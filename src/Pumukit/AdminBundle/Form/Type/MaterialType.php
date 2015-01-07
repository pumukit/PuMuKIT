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
      ->add('mime_type', 'choice', array(
                'choices' => array(
                                   '1' => 'xxx - ', 
                                   '2' => 'zip - Compress file',
                                   '3' => 'tgz - Compress file',
                                   '4' => 'tar - Backup file',
                                   '5' => 'swf - Flash file',
                                   '6' => 'rar - Compress file',
                                   '7' => 'ppt - Power Point file',
                                   '8' => 'pps - Power Point file',
                                   '9' => 'pdf - PDF file',
                                   '10' => 'mp3 - MP3 file',
                                   '11' => 'gz - Compress file',
                                   '12' => 'doc - Word file',
                                   '13' => 'srt - Text-captions srt'
                                   ),
                'label' => 'Type'))
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
