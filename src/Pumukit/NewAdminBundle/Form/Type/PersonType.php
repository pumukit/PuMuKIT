<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

class PersonType extends AbstractType
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
            ->add('i18n_honorific', 'texti18n',
                  array(
                        'required' => false,
                        'attr' => array('aria-label' => $this->translator->trans('Honorific', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Honorific', array(), null, $this->locale), ))
            ->add('name', 'text',
                  array(
                        'attr' => array('aria-label' => $this->translator->trans('Name', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Name', array(), null, $this->locale), ))
            ->add('i18n_post', 'texti18n',
                  array(
                        'required' => false,
                        'attr' => array('aria-label' => $this->translator->trans('Post', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Post', array(), null, $this->locale), ))
            ->add('i18n_firm', 'texti18n',
                  array(
                        'required' => false,
                        'attr' => array('aria-label' => $this->translator->trans('Firm', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Firm', array(), null, $this->locale), ))
            ->add('i18n_bio', 'textareai18n',
                  array(
                        'required' => false,
                        'attr' => array('style' => 'resize:vertical;', 'aria-label' => $this->translator->trans('Bio', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Bio', array(), null, $this->locale), ))
            ->add('email', 'email',
                  array(
                        'required' => false,
                        'attr' => array('aria-label' => $this->translator->trans('Email', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Email', array(), null, $this->locale), ))
            ->add('web', 'url',
                  array(
                        'required' => false,
                        'pattern' => '^https?:\/\/.*',
                        'attr' => array('aria-label' => $this->translator->trans('Web', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Web', array(), null, $this->locale), ))
            ->add('phone', 'text',
                  array(
                        'required' => false,
                        'attr' => array('aria-label' => $this->translator->trans('Phone', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Phone', array(), null, $this->locale), ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
        'data_class' => 'Pumukit\SchemaBundle\Document\Person',
    ));
    }

    public function getName()
    {
        return 'pumukitnewadmin_person';
    }
}
