<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

class LinkType extends AbstractType
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
                array('required' => true,
                      'attr' => array('aria-label' => $this->translator->trans('Name', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Name', array(), null, $this->locale)))
          ->add('url', 'url', array('required' => true,
                                    'attr' => array('aria-label' => $this->translator->trans('URL', array(), null, $this->locale),
                                                    'oninvalid' => "setCustomValidity('Please enter a URL with scheme (example http://pumukit.org/path/file.pdf) ')",
                                                    'onchange' => "setCustomValidity('')", ),
                                    'label' => $this->translator->trans('URL', array(), null, $this->locale), ))
      ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
        'data_class' => 'Pumukit\SchemaBundle\Document\Link',
    ));
    }

    public function getName()
    {
        return 'pumukitnewadmin_link';
    }
}
