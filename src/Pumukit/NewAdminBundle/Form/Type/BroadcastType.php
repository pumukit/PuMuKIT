<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Pumukit\SchemaBundle\Document\Broadcast;
use Symfony\Component\Translation\TranslatorInterface;

class BroadcastType extends AbstractType
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
            ->add('name', 'text',
                  array(
                      'attr' => array('aria-label' => $this->translator->trans('Name', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Name', array(), null, $this->locale), ))
            ->add('broadcast_type_id', 'choice',
                  array('choices' => array(
                      Broadcast::BROADCAST_TYPE_PUB => Broadcast::BROADCAST_TYPE_PUB,
                      Broadcast::BROADCAST_TYPE_PRI => Broadcast::BROADCAST_TYPE_PRI,
                      Broadcast::BROADCAST_TYPE_COR => Broadcast::BROADCAST_TYPE_COR, ),
                        'attr' => array('aria-label' => $this->translator->trans('Type', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Type', array(), null, $this->locale), ))
            ->add('passwd', 'text',
                  array('label' => $this->translator->trans('Passwd', array(), null, $this->locale),
                        'attr' => array('aria-label' => $this->translator->trans('Passwd', array(), null, $this->locale)),
                        'required' => false, ))
            ->add('i18n_description', 'textareai18n',
                  array(
                      'attr' => array('aria-label' => $this->translator->trans('Description', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Description', array(), null, $this->locale), ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pumukit\SchemaBundle\Document\Broadcast',
        ));
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_broadcast';
    }
}
