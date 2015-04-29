<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

class MaterialType extends AbstractType
{
    private $translator;
    private $locale;

    public function __construct(TranslatorInterface $translator, $locale='en')
    {
        $this->translator = $translator;
        $this->locale = $locale;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
          ->add('i18n_name', 'texti18n', array('required' => true, 'label' => $this->translator->trans('Name', array(), null, $this->locale)))
          ->add('hide', 'checkbox', array('required' => false, 'label' => $this->translator->trans('Hide', array(), null, $this->locale)))
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
                'label' => $this->translator->trans('Type')))
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
        return 'pumukitnewadmin_material';
    }
}
