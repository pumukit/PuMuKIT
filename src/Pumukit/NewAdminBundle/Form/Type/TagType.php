<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

class TagType extends AbstractType
{
    private $translator;
    private $locale;

    public function __construct(TranslatorInterface $translator, $locale='en')
    {
        $this->translator = $translator;
        $this->locale = $locale;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('metatag', 'checkbox', array('required' => false))
            ->add('display', 'checkbox', array('required' => false))
            ->add('cod', 'text', array(
                                       'attr' => array(
                                                       'pattern' => "^\w*$",
                                                       'oninvalid' => "setCustomValidity('The code can not have blank spaces neither special characters')",
                                                       'oninput' => "setCustomValidity('')"),
                                       'label' => $this->translator->trans('Cod', array(), null, $this->locale)))
            ->add('i18n_title', 'texti18n',
                  array('label' => $this->translator->trans('Title', array(), null, $this->locale)))
            ->add('i18n_description', 'textareai18n',
                  array('required' => false, 'label' => $this->translator->trans('Description', array(), null, $this->locale)))
      ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
        'data_class' => 'Pumukit\SchemaBundle\Document\Tag',
    ));
    }

    public function getName()
    {
        return 'pumukitnewadmin_tag';
    }
}
