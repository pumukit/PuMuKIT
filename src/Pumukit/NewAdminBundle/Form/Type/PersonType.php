<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextareaI18nType;

class PersonType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $builder
            ->add('i18n_honorific', TextI18nType::class,
                  array(
                      'required' => false,
                      'attr' => array('aria-label' => $this->translator->trans('Honorific', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Honorific', array(), null, $this->locale), ))
            ->add('name', TextType::class,
                  array(
                      'attr' => array('aria-label' => $this->translator->trans('Name', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Name', array(), null, $this->locale), ))
            ->add('i18n_post', TextI18nType::class,
                  array(
                      'required' => false,
                      'attr' => array('aria-label' => $this->translator->trans('Post', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Post', array(), null, $this->locale), ))
            ->add('i18n_firm', TextI18nType::class,
                  array(
                      'required' => false,
                      'attr' => array('aria-label' => $this->translator->trans('Firm', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Firm', array(), null, $this->locale), ))
            ->add('i18n_bio', TextareaI18nType::class,
                  array(
                      'required' => false,
                      'attr' => array('style' => 'resize:vertical;', 'aria-label' => $this->translator->trans('Bio', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Bio', array(), null, $this->locale), ))
            ->add('email', EmailType::class,
                  array(
                      'required' => false,
                      'attr' => array('aria-label' => $this->translator->trans('Email', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Email', array(), null, $this->locale), ))
            ->add('web', UrlType::class,
                  array(
                      'required' => false,
                      'attr' => array('aria-label' => $this->translator->trans('Web', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Web', array(), null, $this->locale), ))
            ->add('phone', TextType::class,
                  array(
                      'required' => false,
                      'attr' => array('aria-label' => $this->translator->trans('Phone', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Phone', array(), null, $this->locale), ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pumukit\SchemaBundle\Document\Person',
        ));

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_person';
    }
}
