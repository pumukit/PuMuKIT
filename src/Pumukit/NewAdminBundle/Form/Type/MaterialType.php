<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nType;
use Pumukit\NewAdminBundle\Form\Type\Base\CustomLanguageType;

class MaterialType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $builder
            ->add('i18n_name', TextI18nType::class,
                  array('required' => true,
                        'attr' => array('aria-label' => $this->translator->trans('Name', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Name', array(), null, $this->locale), ))
            ->add('hide', CheckboxType::class,
                  array('required' => false,
                        'attr' => array('aria-label' => $this->translator->trans('Hide', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Hide', array(), null, $this->locale), ))
            ->add('language', CustomLanguageType::class,
                  array(
                      'required' => true,
                      'attr' => array('aria-label' => $this->translator->trans('Language', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Language', array(), null, $this->locale), ))
            ->add('mime_type', ChoiceType::class, array(
                'attr' => array('aria-label' => $this->translator->trans('Type', array(), null, $this->locale)),
                'choices' => array_flip(array(
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
                )),
                'label' => $this->translator->trans('Type'), ))
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pumukit\SchemaBundle\Document\Material',
        ));

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_material';
    }
}
