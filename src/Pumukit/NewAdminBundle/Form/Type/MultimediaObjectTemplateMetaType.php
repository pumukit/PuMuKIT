<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Pumukit\NewAdminBundle\Form\Type\Other\Html5dateType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextareaI18nType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nAdvanceType;

class MultimediaObjectTemplateMetaType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $builder
            ->add('i18n_description', TextareaI18nType::class,
                  array(
                      'required' => false,
                      'attr' => array('style' => 'resize:vertical;', 'aria-label' => $this->translator->trans('Description', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Description', array(), null, $this->locale), ))
            ->add('i18n_keyword', TextI18nAdvanceType::class,
                  array(
                      'required' => false,
                      'attr' => array('class' => 'mmobj materialtags', 'aria-label' => $this->translator->trans('Keywords', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Keywords', array(), null, $this->locale), ))
            ->add('copyright', TextType::class,
                  array(
                      'required' => false,
                      'attr' => array('aria-label' => $this->translator->trans('Copyright', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Copyright', array(), null, $this->locale), ))
            ->add('public_date', Html5dateType::class,
                  array(
                      'data_class' => 'DateTime',
                      'attr' => array('aria-label' => $this->translator->trans('Publication Date', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Publication Date', array(), null, $this->locale), ))
            ->add('record_date', Html5dateType::class,
                  array(
                      'data_class' => 'DateTime',
                      'attr' => array('aria-label' => $this->translator->trans('Recording Date', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Recording Date', array(), null, $this->locale), ))
            ->add('i18n_line2', TextareaI18nType::class,
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

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_mmtemplate_meta';
    }
}
