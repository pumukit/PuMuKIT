<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

class SeriestypeType extends AbstractType
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
          ->add('cod', 'text',
                array('required' => true,
                      'attr' => array('aria-label' => $this->translator->trans('Code', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Code', array(), null, $this->locale)))
          ->add('i18n_name', 'texti18n',
                array('required' => true,
                      'attr' => array('style' => 'resize:vertical;', 'aria-label' => $this->translator->trans('Name', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Name', array(), null, $this->locale), ))
          ->add('i18n_description', 'textareai18n',
                array('required' => false,
                      'attr' => array('aria-label' => $this->translator->trans('Description', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Description', array(), null, $this->locale)))
          ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
        'data_class' => 'Pumukit\SchemaBundle\Document\SeriesType',
    ));
    }

    public function getName()
    {
        return 'pumukitnewadmin_seriestype';
    }
}
