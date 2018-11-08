<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

class PlaylistType extends AbstractType
{
    private $translator;
    private $locale;
    private $disablePudenew;

    public function __construct(TranslatorInterface $translator, $locale = 'en', $disablePudenew = true)
    {
        $this->translator = $translator;
        $this->locale = $locale;
        $this->disablePudenew = $disablePudenew;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('i18n_title', 'texti18n',
                  array(
                        'attr' => array('aria-label' => $this->translator->trans('Title', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Title', array(), null, $this->locale), ))
            ->add('i18n_description', 'textareai18n',
                  array(
                        'required' => false,
                        'attr' => array('style' => 'resize:vertical;', 'aria-label' => $this->translator->trans('Description', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Description', array(), null, $this->locale), ))
            ->add('i18n_keyword', 'texti18nadvance',
                  array(
                        'required' => false,
                        'attr' => array('aria-label' => $this->translator->trans('Keywords', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Keywords', array(), null, $this->locale), ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
        'data_class' => 'Pumukit\SchemaBundle\Document\Series',
    ));
    }

    public function getName()
    {
        return 'pumukitnewadmin_series';
    }
}
