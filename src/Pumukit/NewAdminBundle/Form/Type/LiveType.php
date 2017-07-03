<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Pumukit\LiveBundle\Document\Live;
use Pumukit\NewAdminBundle\Form\Type\Other\LivequalitiesType;
use Pumukit\NewAdminBundle\Form\Type\Other\LiveresolutionType;
use Symfony\Component\Translation\TranslatorInterface;

class LiveType extends AbstractType
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
            ->add('i18n_name', 'texti18n',
                  array(
                        'attr' => array('aria-label' => $this->translator->trans('Name', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Name', array(), null, $this->locale), ))
            ->add('i18n_description', 'textareai18n',
                  array(
                        'required' => false,
                        'attr' => array('style' => 'resize:vertical;', 'aria-label' => $this->translator->trans('Description', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Description', array(), null, $this->locale), ))
            ->add('url', 'url',
                  array(
                        'attr' => array('aria-label' => $this->translator->trans('URL', array(), null, $this->locale)),
                        'label' => $this->translator->trans('URL', array(), null, $this->locale), ))
            ->add('source_name', 'text',
                  array(
                        'attr' => array('aria-label' => $this->translator->trans('STREAM', array(), null, $this->locale)),
                        'label' => $this->translator->trans('STREAM', array(), null, $this->locale), ))
            ->add('passwd', 'text',
                  array(
                      'required' => false,
                      'attr' => array('aria-label' => $this->translator->trans('Password', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Password', array(), null, $this->locale), ))
            ->add('broadcasting', 'choice',
                  array(
                        'choices' => array('0' => 'On hold', '1' => 'Live Broadcasting'),
                        'attr' => array('aria-label' => $this->translator->trans('Status', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Status', array(), null, $this->locale), ))
            ->add('live_type', 'choice',
                  array(
                        'attr' => array('aria-label' => $this->translator->trans('Technology', array(), null, $this->locale)),
                        'choices' => array(Live::LIVE_TYPE_FMS => 'FMS', Live::LIVE_TYPE_WMS => 'WMS'),
                        'label' => $this->translator->trans('Technology', array(), null, $this->locale), ));
        /*
            ->add('resolution', new LiveresolutionType(),
                  array(
                        'label' => $this->translator->trans('Resolution', array(), null, $this->locale),
                        'required' => false))
            ->add('qualities', new LivequalitiesType(),
                  array(
                        'label' => $this->translator->trans('Qualities', array(), null, $this->locale),
                        'required' => false))
            ->add('ip_source', 'text',
                  array(
                        'required' => false,
                        'label' => $this->translator->trans('IP source', array(), null, $this->locale)));
        */
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
        'data_class' => 'Pumukit\LiveBundle\Document\Live',
    ));
    }

    public function getName()
    {
        return 'pumukitnewadmin_live';
    }
}
