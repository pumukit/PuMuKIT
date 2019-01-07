<?php

namespace Pumukit\NewAdminBundle\Form\Type\Base;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractI18nType extends AbstractType
{
    private $locales;

    public function __construct(array $locales = array())
    {
        $this->locales = $locales;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'compound' => false,
        ));
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['locales'] = $this->locales;
    }
}
