<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Form\Type;

use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nType;
use Pumukit\SchemaBundle\Document\Link;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Url;

class LinkType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options): void
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
                    'default_protocol' => 'http',
                    'constraints' => [new Url(protocols: ['http', 'https'], requireTld: false)],
                    'required' => true,
                    'attr' => [
                        'aria-label' => $this->translator->trans('URL', [], null, $this->locale),
                        'data-action' => 'invalid->admin--actions#validity change->admin--actions#validity',
                        'data-admin--actions-message-param' => 'Please enter a URL with scheme (example http://pumukit.org/path/file.pdf) ',
                    ],
                    'label' => $this->translator->trans('URL', [], null, $this->locale),
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Link::class,
                'translation_domain' => false,
            ]
        );

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix(): string
    {
        return 'pumukitnewadmin_link';
    }
}
