<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Pumukit\SchemaBundle\Document\UserClearance;
use Symfony\Component\Translation\TranslatorInterface;

class UserClearanceType extends AbstractType
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
            ->add('name', 'text',
                  array('label' => $this->translator->trans('Name', array(), null, $this->locale)))
            ->add('system', 'checkbox',
                  array('required' => false,
                        'label' => $this->translator->trans('System', array(), null, $this->locale)))
            ->add('default', 'checkbox',
                  array('required' => false,
                        'label' => $this->translator->trans('Default', array(), null, $this->locale)))
            ->add('scope', 'choice',
                  array('choices' => UserClearance::$scopeDescription,
                        'required' => false,
                        'label' => $this->translator->trans('Scope', array(), null, $this->locale)))
;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
                                     'data_class' => 'Pumukit\SchemaBundle\Document\UserClearance',
                                     ));
    }

    public function getName()
    {
        return 'pumukitnewadmin_userclearance';
    }
}