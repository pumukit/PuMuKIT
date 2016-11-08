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

    public function __construct(TranslatorInterface $translator, $locale = 'en')
    {
        $this->translator = $translator;
        $this->locale = $locale;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
          ->add('i18n_name', 'texti18n', array('required' => true, 'label' => $this->translator->trans('Name', array(), null, $this->locale)))
          ->add('hide', 'checkbox', array('required' => false, 'label' => $this->translator->trans('Hide', array(), null, $this->locale)))
          ->add('language', 'customlanguage',
                array(
                      'required' => true,
                      'label' => $this->translator->trans('Language', array(), null, $this->locale), ))
          ->add('mime_type', 'choice', array(
                'choices' => array(
                                   'xxx' => 'xxx - ',
                                   'zip' => 'zip - Compress file',
                                   'tgz' => 'tgz - Compress file',
                                   'tar' => 'tar - Backup file',
                                   'swf' => 'swf - Flash file',
                                   'rar' => 'rar - Compress file',
                                   'ppt' => 'ppt - Power Point file',
                                   'pps' => 'pps - Power Point file',
                                   'pdf' => 'pdf - PDF file',
                                   'mp3' => 'mp3 - MP3 file',
                                   'gz' => 'gz - Compress file',
                                   'doc' => 'doc - Word file',
                                   'srt' => 'srt - Text-captions srt',
                                   'vtt' => 'vtt - Video Text Tracks',
                                   'dfxp' => 'dfxp - Distribution Format Exchange Profile',
                                   ),
                'label' => $this->translator->trans('Type'), ))
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
