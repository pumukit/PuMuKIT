<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $builder
            ->add('key', TextType::class,
                  array(
                      'attr' => array(
                          'aria-label' => $this->translator->trans('Key', array(), null, $this->locale),
                          'pattern' => "^\w*$",
                          'oninvalid' => "setCustomValidity('The key can not have blank spaces neither special characters')",
                          'oninput' => "setCustomValidity('')", ),
                      'label' => $this->translator->trans('Key', array(), null, $this->locale), ))
            ->add('name', TextType::class,
                  array(
                      'attr' => array('aria-label' => $this->translator->trans('Name', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Name', array(), null, $this->locale), ))
            ->add('comments', TextareaType::class,
                  array(
                      'attr' => array('style' => 'resize:vertical;', 'aria-label' => $this->translator->trans('Comments', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Comments', array(), null, $this->locale),
                      'required' => false, ))
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pumukit\SchemaBundle\Document\Group',
        ));

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_group';
    }
}
