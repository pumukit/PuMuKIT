<?php

namespace Pumukit\LegacyWebTVBundle\Form\Type;

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
