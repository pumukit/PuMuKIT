<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Pumukit\NewAdminBundle\Form\Type\Other\Html5dateType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Pumukit\SchemaBundle\Document\EmbeddedEventSession;

class EmbeddedEventSessionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translator = $options['translator'];
        $locale = $options['locale'];

        $builder
            ->add(
                'start',
                Html5dateType::class,
                [
                    'data_class' => 'DateTime',
                    'label' => $translator->trans('Start', [], null, $locale),
                    'attr' => ['class' => 'form-control'],
                ]
            )
            ->add(
                'ends',
                Html5dateType::class,
                [
                    'data_class' => 'DateTime',
                    'label' => $translator->trans('End', [], null, $locale),
                    'required' => false,
                    'attr' => ['class' => 'form-control'],
                ]
            )
            ->add(
                'notes',
                TextareaType::class,
                [
                    'label' => $translator->trans('Notes', [], null, $locale),
                    'required' => false,
                    'attr' => ['class' => 'form-control'],
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmbeddedEventSession::class,
        ]);

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix(): string
    {
        return 'pumukitnewadmin_event_session';
    }
}
