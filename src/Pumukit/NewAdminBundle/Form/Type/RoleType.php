<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Pumukit\SchemaBundle\Document\Role;
use Symfony\Component\Translation\TranslatorInterface;

class RoleType extends AbstractType
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
            ->add('display', 'checkbox', array('required' => false))
            ->add('cod', 'text', array(
                                       'attr' => array(
                                                       'pattern' => "^\w*$",
                                                       'oninvalid' => "setCustomValidity('The code can not have blank spaces neither special characters')",
                                                       'oninput' => "setCustomValidity('')", ),
                                       'label' => $this->translator->trans('Code', array(), null, $this->locale), ))
            ->add('xml', 'text',
                  array('label' => $this->translator->trans('XML', array(), null, $this->locale)))
            ->add('i18n_name', 'texti18n',
                  array('label' => $this->translator->trans('Name', array(), null, $this->locale)))
            ->add('i18n_text', 'textareai18n',
                  array(
                        'required' => false,
                        'attr' => array('style' => 'resize:vertical;'),
                        'label' => $this->translator->trans('Text', array(), null, $this->locale), ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
        'data_class' => 'Pumukit\SchemaBundle\Document\Role',
    ));
    }

    public function getName()
    {
        return 'pumukitnewadmin_role';
    }
}
