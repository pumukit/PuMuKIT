<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\NewAdminBundle\Form\Type\Other\Html5dateType;
use Symfony\Component\Translation\TranslatorInterface;

class PlaylistType extends AbstractType
{
    private $translator;
    private $locale;

    public function __construct(TranslatorInterface $translator, $locale='en', $disablePudenew = true)
    {
        $this->translator = $translator;
        $this->locale = $locale;
        $this->disablePudenew = $disablePudenew;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('i18n_title', 'texti18n',
                  array('label' => $this->translator->trans('Title', array(), null, $this->locale)))
            ->add('i18n_keyword', 'texti18n',
                  array(
                        'required' => false,
                        'label' => $this->translator->trans('Keyword', array(), null, $this->locale)))
            ->add('i18n_description', 'textareai18n',
                  array(
                        'required' => false,
                        'attr' => array('style' => 'resize:vertical;'),
                        'label' => $this->translator->trans('Description', array(), null, $this->locale)));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
        'data_class' => 'Pumukit\SchemaBundle\Document\Series',
    ));
    }

    public function getName()
    {
        return 'pumukitnewadmin_series';
    }
}
