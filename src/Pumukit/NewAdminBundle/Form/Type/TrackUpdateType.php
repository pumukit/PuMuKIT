<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Pumukit\NewAdminBundle\Form\Type\Other\TrackresolutionType;
use Pumukit\NewAdminBundle\Form\Type\Other\TrackdurationType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nType;
use Pumukit\NewAdminBundle\Form\Type\Base\CustomLanguageType;

class TrackUpdateType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $builder
            ->add('i18n_description', TextI18nType::class,
                  array(
                      'required' => false,
                      'attr' => array('aria-label' => $this->translator->trans('Description', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Description', array(), null, $this->locale), ))
            ->add('hide', CheckboxType::class,
                  array(
                      'required' => false,
                      'attr' => array('aria-label' => $this->translator->trans('Hide', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Hide', array(), null, $this->locale), ))
            ->add('allowDownload', CheckboxType::class,
                  array(
                      'required' => false,
                      'attr' => array('aria-label' => $this->translator->trans('Allow download', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Allow download', array(), null, $this->locale), ))
            ->add('language', CustomLanguageType::class,
                  array(
                      'required' => true,
                      'attr' => array('aria-label' => $this->translator->trans('Video/Audio language', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Video/Audio language', array(), null, $this->locale), ))
            ->add('durationinminutesandseconds', new TrackdurationType(),
                  array(
                      'required' => true,
                      'disabled' => true,
                      'attr' => array('aria-label' => $this->translator->trans('Duration', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Duration', array(), null, $this->locale), ))
            ->add('resolution', new TrackresolutionType(),
                  array(
                      'required' => true,
                      'disabled' => true,
                      'attr' => array('aria-label' => $this->translator->trans('Resolution', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Resolution', array(), null, $this->locale), ))
            ->add('size', 'integer',
                  array(
                      'required' => true,
                      'disabled' => true,
                      'attr' => array('aria-label' => $this->translator->trans('Size', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Size', array(), null, $this->locale), ))
            ->add('path', TextType::class,
                  array(
                      'required' => true,
                      'disabled' => true,
                      'attr' => array('aria-label' => $this->translator->trans('File', array(), null, $this->locale)),
                      'label' => $this->translator->trans('File', array(), null, $this->locale), ))
            ->add('url', TextType::class,
                  array(
                      'required' => true,
                      'disabled' => true,
                      'attr' => array('aria-label' => $this->translator->trans('URL', array(), null, $this->locale)),
                      'label' => $this->translator->trans('URL', array(), null, $this->locale), ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pumukit\SchemaBundle\Document\Track',
        ));

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_track_update';
    }
}
