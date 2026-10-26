<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Form\Type;

use Pumukit\NewAdminBundle\Form\Type\Base\TextareaI18nType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nType;
use Pumukit\SchemaBundle\Document\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RoleType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $builder
            ->add(
                'display',
                CheckboxType::class,
                [
                    'translation_domain' => 'messages',
                    'required' => false,
                    'attr' => ['aria-label' => $this->translator->trans('Display', [], null, $this->locale)],
                ]
            )
            ->add(
                'read_only',
                CheckboxType::class,
                [
                    'translation_domain' => 'messages',
                    'required' => false,
                    'attr' => ['aria-label' => $this->translator->trans('Read only', [], null, $this->locale)],
                ]
            )
            ->add(
                'cod',
                TextType::class,
                [
                    'attr' => [
                        'pattern' => '^\w*$',
                        'data-action' => 'invalid->admin--actions#validity input->admin--actions#validity',
                        'data-admin--actions-message-param' => 'The code can not have blank spaces neither special characters',
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
                    'attr' => ['class' => 'pmk-resize-vertical', 'aria-label' => $this->translator->trans('Text', [], null, $this->locale)],
                    'label' => $this->translator->trans('Text', [], null, $this->locale),
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Role::class,
                'translation_domain' => false,
            ]
        );

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix(): string
    {
        return 'pumukitnewadmin_role';
    }
}
