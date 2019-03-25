<?php

namespace Pumukit\WebTVBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class ContactType.
 */
class ContactType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    private $locale;

    /**
     * ContactType constructor.
     *
     * @param TranslatorInterface $translator
     * @param string              $locale
     */
    public function __construct(TranslatorInterface $translator, $locale = 'en')
    {
        $this->translator = $translator;
        $this->locale = $locale;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'label' => $this->translator->trans('Name', [], null, $this->locale),
                    'attr' => ['class' => 'form-control'],
                    'required' => true,
                ]
            )
            ->add(
                'email',
                EmailType::class,
                [
                    'label' => $this->translator->trans('Email', [], null, $this->locale),
                    'attr' => ['class' => 'form-control'],
                    'required' => true,
                ]
            )
            ->add(
                'content',
                TextareaType::class,
                [
                    'label' => $this->translator->trans('Content', [], null, $this->locale),
                    'attr' => ['class' => 'form-control'],
                    'required' => true,
                ]
            );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'pumukit_multimedia_object_contact';
    }
}
