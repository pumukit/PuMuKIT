<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Pumukit\NewAdminBundle\Form\Type\Base\CustomLanguageType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MaterialType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $builder
            ->add(
                'i18n_name',
                TextI18nType::class,
                [
                    'required' => true,
                    'attr' => ['aria-label' => $this->translator->trans('Name', [], null, $this->locale)],
                    'label' => $this->translator->trans('Name', [], null, $this->locale),
                ]
            )
            ->add(
                'hide',
                CheckboxType::class,
                [
                    'required' => false,
                    'attr' => ['aria-label' => $this->translator->trans('Hide', [], null, $this->locale)],
                    'label' => $this->translator->trans('Hide', [], null, $this->locale),
                ]
            )
            ->add(
                'language',
                CustomLanguageType::class,
                [
                    'required' => true,
                    'attr' => ['aria-label' => $this->translator->trans('Language', [], null, $this->locale)],
                    'label' => $this->translator->trans('Language', [], null, $this->locale),
                ]
            )
            ->add(
                'mime_type',
                ChoiceType::class,
                [
                    'attr' => ['aria-label' => $this->translator->trans('Type', [], null, $this->locale)],
                    'choices' => array_flip(
                        [
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
                        ]
                    ),
                    'label' => $this->translator->trans('Type'),
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Pumukit\SchemaBundle\Document\Material',
            ]
        );

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_material';
    }
}
