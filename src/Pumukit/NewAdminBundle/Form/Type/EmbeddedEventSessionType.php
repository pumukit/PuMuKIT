<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Pumukit\NewAdminBundle\Form\Type\Other\Html5dateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmbeddedEventSessionType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $builder
            ->add('start', Html5dateType::class,
                  array('data_class' => 'DateTime', 'label' => $this->translator->trans('Start', array(), null, $this->locale), 'attr' => array('class' => 'form-control')))
            ->add('duration', Html5dateType::class,
                  array('data_class' => 'DateTime', 'label' => $this->translator->trans('End', array(), null, $this->locale),
                        'required' => false, 'attr' => array('class' => 'form-control'), ))
            ->add('notes', TextareaType::class,
                  array('label' => $this->translator->trans('Notes', array(), null, $this->locale), 'required' => false, 'attr' => array('class' => 'form-control')));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pumukit\SchemaBundle\Document\EmbeddedEventSession',
        ));

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_event_session';
    }
}
