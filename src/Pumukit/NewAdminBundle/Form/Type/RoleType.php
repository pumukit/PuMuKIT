<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Pumukit\NewAdminBundle\Form\Type\Base\TextareaI18nType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RoleType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $builder
            ->add(
                'display',
                CheckboxType::class,
                [
                    'required' => false,
                    'attr' => ['aria-label' => $this->translator->trans('Display', [], null, $this->locale)],
                ]
            )
            ->add(
                'read_only',
                CheckboxType::class,
                [
                    'required' => false,
                    'attr' => ['aria-label' => $this->translator->trans('Read only', [], null, $this->locale)],
                ]
            )
            ->add(
                'cod',
                TextType::class,
                [
                    'attr' => [
                        'pattern' => '^\\w*$',
                        'oninvalid' => "setCustomValidity('The code can not have blank spaces neither special characters')",
                        'oninput' => "setCustomValidity('')",
                    ],
                    'label' => $this->translator->trans('Code', [], null, $this->locale),
                ]
            )
            ->add(
                'xml',
                TextType::class,
                [
                    'attr' => ['aria-label' => $this->translator->trans('XML', [], null, $this->locale)],
                    'label' => $this->translator->trans('XML', [], null, $this->locale),
                ]
            )
            ->add(
                'i18n_name',
                TextI18nType::class,
                [
                    'attr' => ['aria-label' => $this->translator->trans('Name', [], null, $this->locale)],
                    'label' => $this->translator->trans('Name', [], null, $this->locale),
                ]
            )
            ->add(
                'i18n_text',
                TextareaI18nType::class,
                [
                    'required' => false,
                    'attr' => ['style' => 'resize:vertical;', 'aria-label' => $this->translator->trans('Text', [], null, $this->locale)],
                    'label' => $this->translator->trans('Text', [], null, $this->locale),
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Pumukit\SchemaBundle\Document\Role',
            ]
        );

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_role';
    }
}
