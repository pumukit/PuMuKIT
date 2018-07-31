<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Pumukit\NewAdminBundle\Form\Type\Other\Html5dateType;
use Symfony\Component\Translation\TranslatorInterface;

class MultimediaObjectTemplateMetaType extends AbstractType
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
            ->add('i18n_description', 'textareai18n',
                  array(
                      'required' => false,
                      'attr' => array('style' => 'resize:vertical;', 'aria-label' => $this->translator->trans('Description', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Description', array(), null, $this->locale), ))
            ->add('i18n_keyword', 'texti18nadvance',
                  array(
                      'required' => false,
                      'attr' => array('class' => 'mmobj materialtags', 'aria-label' => $this->translator->trans('Keywords', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Keywords', array(), null, $this->locale), ))
            ->add('copyright', 'text',
                  array(
                      'required' => false,
                      'attr' => array('aria-label' => $this->translator->trans('Copyright', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Copyright', array(), null, $this->locale), ))
            ->add('public_date', new Html5dateType(),
                  array(
                      'data_class' => 'DateTime',
                      'attr' => array('aria-label' => $this->translator->trans('Publication Date', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Publication Date', array(), null, $this->locale), ))
            ->add('record_date', new Html5dateType(),
                  array(
                      'data_class' => 'DateTime',
                      'attr' => array('aria-label' => $this->translator->trans('Recording Date', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Recording Date', array(), null, $this->locale), ))
            ->add('i18n_line2', 'textareai18n',
                  array(
                      'required' => false,
                      'attr' => array('groupclass' => 'hidden-naked',
                                      'style' => 'resize:vertical;',
                                      'aria-label' => $this->translator->trans('Headline', array(), null, $this->locale), ),
                      'label' => $this->translator->trans('Headline', array(), null, $this->locale), ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pumukit\SchemaBundle\Document\MultimediaObject',
        ));
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_mmtemplate_meta';
    }
}
