<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextareaI18nType;

class RoleType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $builder
            ->add('display', CheckboxType::class,
                  array('required' => false,
                        'attr' => array('aria-label' => $this->translator->trans('Display', array(), null, $this->locale)), ))
            ->add('read_only', CheckboxType::class,
                  array('required' => false,
                        'attr' => array('aria-label' => $this->translator->trans('Read only', array(), null, $this->locale)), ))
            ->add('cod', TextType::class, array(
                'attr' => array(
                    'pattern' => "^\w*$",
                    'oninvalid' => "setCustomValidity('The code can not have blank spaces neither special characters')",
                    'oninput' => "setCustomValidity('')", ),
                'label' => $this->translator->trans('Code', array(), null, $this->locale), ))
            ->add('xml', TextType::class,
                  array(
                      'attr' => array('aria-label' => $this->translator->trans('XML', array(), null, $this->locale)),
                      'label' => $this->translator->trans('XML', array(), null, $this->locale), ))
            ->add('i18n_name', TextI18nType::class,
                  array(
                      'attr' => array('aria-label' => $this->translator->trans('Name', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Name', array(), null, $this->locale), ))
            ->add('i18n_text', TextareaI18nType::class,
                  array(
                      'required' => false,
                      'attr' => array('style' => 'resize:vertical;', 'aria-label' => $this->translator->trans('Text', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Text', array(), null, $this->locale), ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pumukit\SchemaBundle\Document\Role',
        ));

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_role';
    }
}
