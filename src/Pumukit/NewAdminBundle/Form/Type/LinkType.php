<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LinkType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $builder
            ->add(
                'i18n_name',
                TextI18nType::class,
                [
                    'required' => true,
                    'attr' => ['aria-label' => $this->translator->trans('Name', [], null, $this->locale)],
                    'label' => $this->translator->trans('Name', [], null, $this->locale),
                ]
            )
            ->add(
                'url',
                UrlType::class,
                [
                    'required' => true,
                    'attr' => [
                        'aria-label' => $this->translator->trans('URL', [], null, $this->locale),
                        'oninvalid' => "setCustomValidity('Please enter a URL with scheme (example http://pumukit.org/path/file.pdf) ')",
                        'onchange' => "setCustomValidity('')",
                    ],
                    'label' => $this->translator->trans('URL', [], null, $this->locale),
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Pumukit\SchemaBundle\Document\Link',
            ]
        );

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_link';
    }
}
