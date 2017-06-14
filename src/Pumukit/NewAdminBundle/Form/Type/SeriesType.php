<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Pumukit\NewAdminBundle\Form\Type\Other\Html5dateType;
use Symfony\Component\Translation\TranslatorInterface;

class SeriesType extends AbstractType
{
    private $translator;
    private $locale;
    private $disablePudenew;

    public function __construct(TranslatorInterface $translator, $locale = 'en', $disablePudenew = true)
    {
        $this->translator = $translator;
        $this->locale = $locale;
        $this->disablePudenew = $disablePudenew;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('announce', 'checkbox',
                  array(
                        'label_attr' => $this->disablePudenew ? array('class' => 'pmk_disabled_checkbox') : array(),
                        'disabled' => $this->disablePudenew,
                        'required' => false,
                        'label' => $this->translator->trans('Last Added (Announced)', array(), null, $this->locale), ))
            ->add('i18n_title', 'texti18n',
                  array('label' => $this->translator->trans('Title', array(), null, $this->locale)))
            ->add('i18n_subtitle', 'texti18n',
                  array(
                        'required' => false,
                        'label' => $this->translator->trans('Subtitle', array(), null, $this->locale), ))
            ->add('i18n_description', 'textareai18n',
                  array(
                        'required' => false,
                        'attr' => array('style' => 'resize:vertical;'),
                        'label' => $this->translator->trans('Description', array(), null, $this->locale), ))
            ->add('i18n_keyword', 'texti18n',
                  array(
                        'required' => false,
                        'attr' => array('class' => 'series materialtags'),
                        'label' => $this->translator->trans('Keywords', array(), null, $this->locale), ))
            ->add('copyright', 'text',
                  array(
                        'required' => false,
                        'label' => $this->translator->trans('Copyright', array(), null, $this->locale), ))
            ->add('license', 'license',
                  array(
                        'required' => false,
                        'label' => $this->translator->trans('License', array(), null, $this->locale), ))
            ->add('series_type', null,
                  array(
                        'required' => false,
                        'label' => $this->translator->trans('Channel', array(), null, $this->locale), ))
            ->add('public_date', new Html5dateType(),
                  array(
                        'data_class' => 'DateTime',
                        'label' => $this->translator->trans('Publication Date', array(), null, $this->locale), ))
            ->add('i18n_header', 'textareai18n',
                  array(
                        'required' => false,
                        'attr' => array('groupclass' => 'hidden-naked', 'style' => 'resize:vertical;'),
                        'label' => $this->translator->trans('Header Text', array(), null, $this->locale), ))
            ->add('i18n_footer', 'textareai18n',
                  array(
                        'required' => false,
                        'attr' => array('groupclass' => 'hidden-naked', 'style' => 'resize:vertical;'),
                        'label' => $this->translator->trans('Footer Text', array(), null, $this->locale), ))
            ->add('i18n_line2', 'textareai18n',
                  array(
                        'required' => false,
                        'attr' => array('groupclass' => 'hidden-naked', 'style' => 'resize:vertical;'),
                        'label' => $this->translator->trans('Headline', array(), null, $this->locale), ))
           ->add('template', 'choice',
                  array(
                        'choices' => array('date' => 'date', 'date_all' => 'date_all',
                                           'date_subserial' => 'date_subserial', 'subserial' => 'subserial',
                                           'multisubserial' => 'multisubserial', ),
                        'empty_data' => null,
                        'mapped' => false,
                        'attr' => array('groupclass' => 'hidden-naked'),
                        'required' => true,
                        'label' => $this->translator->trans('Template', array(), null, $this->locale), ));

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $series = $event->getData();
            $event->getForm()->get('template')->setData($series->getProperty('template'));
        });

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $template = $event->getForm()->get('template')->getData();
            $series = $event->getData();
            $series->setProperty('template', $template);
        });
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
