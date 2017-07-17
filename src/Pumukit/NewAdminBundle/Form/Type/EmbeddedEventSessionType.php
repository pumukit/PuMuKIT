<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Pumukit\NewAdminBundle\Form\Type\Other\Html5dateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

class EmbeddedEventSessionType extends AbstractType
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
            ->add('start', new Html5dateType(),
                  array('data_class' => 'DateTime', 'label' => $this->translator->trans('Start', array(), null, $this->locale), 'attr' => array('class' => 'form-control')))
            ->add('duration', new Html5dateType(),
                array('data_class' => 'DateTime', 'label' => $this->translator->trans('End', array(), null, $this->locale),
                        'required' => false, 'attr' => array('class' => 'form-control'), ))
            ->add('notes', TextareaType::class,
                  array('label' => $this->translator->trans('Notes', array(), null, $this->locale), 'attr' => array('class' => 'form-control')));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pumukit\SchemaBundle\Document\EmbeddedEventSession',
        ));
    }

    public function getName()
    {
        return 'pumukitnewadmin_event_session';
    }
}
