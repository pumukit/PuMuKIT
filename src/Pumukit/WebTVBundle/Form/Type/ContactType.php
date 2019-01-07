<?php

namespace Pumukit\WebTVBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ContactType extends AbstractType
{
    private $translator;
    private $locale;

    public function __construct(TranslatorInterface $translator, $locale = 'en')
    {
        $this->translator = $translator;
        $this->locale = $locale;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, array(
                'label' => $this->translator->trans('Name', array(), null, $this->locale),
                'attr' => array('class' => 'form-control'),
                'required' => true,
            ))
            ->add('email', EmailType::class, array(
                'label' => $this->translator->trans('Email', array(), null, $this->locale),
                'attr' => array('class' => 'form-control'),
                'required' => true,
            ))
            ->add('content', TextareaType::class, array(
                'label' => $this->translator->trans('Content', array(), null, $this->locale),
                'attr' => array('class' => 'form-control'),
                'required' => true,
            ));
    }

    public function getBlockPrefix()
    {
        return 'pumukit_multimedia_object_contact';
    }
}
