<?php

namespace Pumukit\WebTVBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

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
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix()
    {
        return 'pumukit_multimedia_object_contact';
    }
}
