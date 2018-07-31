<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class TrackType extends AbstractType
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
            ->add('i18n_description', 'texti18n',
                  array(
                      'required' => false,
                      'attr' => array('aria-label' => $this->translator->trans('Description', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Description', array(), null, $this->locale), ))
            ->add('language', 'customlanguage',
                  array(
                      'required' => true,
                      'attr' => array('aria-label' => $this->translator->trans('Video/Audio language', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Video/Audio language', array(), null, $this->locale), ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pumukit\SchemaBundle\Document\Track',
        ));
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_track';
    }
}
