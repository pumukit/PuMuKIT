<?php

namespace Pumukit\NewAdminBundle\Form\Type\Base;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

abstract class AbstractI18nType extends AbstractType
{
    private $locales;
    private $translators;

    public function __construct(array $locales = array(), array $translators = array())
    {
        $this->locales = $locales;
        $this->translators = $translators;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'compound' => false,
        ));
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['locales'] = $this->locales;
        $view->vars['translators'] = $this->translators;
    }
}
