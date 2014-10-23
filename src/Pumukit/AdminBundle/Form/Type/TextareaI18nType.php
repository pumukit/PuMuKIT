<?php

namespace Pumukit\AdminBundle\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class TextareaI18nType extends AbstractType
{
  private $locales;

  public function __construct(array $locales = array())
  {
    $this->locales = $locales;
  }
			      
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    foreach($this->locales as $locale) {
      $builder->add($locale, 'textarea', array());
    }
  }

  public function buildView(FormView $view, FormInterface $form, array $options)
  {
    $view->vars['locales'] = $this->locales;
  }
  
  public function getName()
  {
    return 'textareai18n';
  }
}