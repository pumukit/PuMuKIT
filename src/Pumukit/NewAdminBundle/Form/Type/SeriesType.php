<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Pumukit\NewAdminBundle\Form\Type\Other\Html5dateType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextareaI18nType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nAdvanceType;
use Pumukit\NewAdminBundle\Form\Type\Base\LicenseType;
use Pumukit\SchemaBundle\Document\Series;

class SeriesType extends AbstractType
{
    private $translator;
    private $locale;
    private $disablePudenew;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];
        $this->disablePudenew = $options['disable_PUDENEW'];

        $builder
            ->add('announce', CheckboxType::class,
                  array(
                      'label_attr' => $this->disablePudenew ? array('class' => 'pmk_disabled_checkbox') : array(),
                      'disabled' => $this->disablePudenew,
                      'required' => false,
                      'attr' => array('aria-label' => $this->translator->trans('Last Added (Announced)', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Last Added (Announced)', array(), null, $this->locale), ))
            ->add('hide', CheckboxType::class,
                  array(
                      'required' => false,
                      'attr' => array('aria-label' => $this->translator->trans('Hide', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Hide', array(), null, $this->locale), ))
            ->add('i18n_title', TextI18nType::class,
                  array('attr' => array('aria-label' => $this->translator->trans('Title', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Title', array(), null, $this->locale), ))
            ->add('i18n_subtitle', TextI18nType::class,
                  array(
                      'required' => false,
                      'attr' => array('aria-label' => $this->translator->trans('Subtitle', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Subtitle', array(), null, $this->locale), ))
            ->add('i18n_description', TextareaI18nType::class,
                  array(
                      'required' => false,
                      'attr' => array('style' => 'resize:vertical;',
                                      'aria-label' => $this->translator->trans('Description', array(), null, $this->locale), ),
                      'label' => $this->translator->trans('Description', array(), null, $this->locale), ))
            ->add('comments', TextareaType::class,
                array('required' => false,
                    'attr' => array('style' => 'resize:vertical;'),
                    'label' => $this->translator->trans('Comments', array(), null, $this->locale), ))
            ->add('i18n_keyword', TextI18nAdvanceType::class,
                  array(
                      'required' => false,
                      'attr' => array('class' => 'series materialtags',
                                      'aria-label' => $this->translator->trans('Keywords', array(), null, $this->locale), ),
                      'label' => $this->translator->trans('Keywords', array(), null, $this->locale), ))
            ->add('copyright', TextType::class,
                  array(
                      'required' => false,
                      'attr' => array('aria-label' => $this->translator->trans('Copyright', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Copyright', array(), null, $this->locale), ))
            ->add('license', LicenseType::class,
                  array(
                      'required' => false,
                      'attr' => array('aria-label' => $this->translator->trans('License', array(), null, $this->locale)),
                      'label' => $this->translator->trans('License', array(), null, $this->locale), ))
            ->add('series_type', null,
                  array(
                      'required' => false,
                      'attr' => array('aria-label' => $this->translator->trans('Channel', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Channel', array(), null, $this->locale), ))
            ->add('series_style', null,
                  array(
                      'required' => false,
                      'attr' => array('aria-label' => $this->translator->trans('Series Style', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Series Style', array(), null, $this->locale), ))
            ->add('public_date', Html5dateType::class,
                  array(
                      'data_class' => 'DateTime',
                      'attr' => array('aria-label' => $this->translator->trans('Publication Date', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Publication Date', array(), null, $this->locale), ))
            ->add('i18n_header', TextareaI18nType::class,
                  array(
                      'required' => false,
                      'attr' => array('groupclass' => 'hidden-naked', 'style' => 'resize:vertical;',
                                      'aria-label' => $this->translator->trans('Header Text', array(), null, $this->locale), ),
                      'label' => $this->translator->trans('Header Text', array(), null, $this->locale), ))
            ->add('i18n_footer', TextareaI18nType::class,
                  array(
                      'required' => false,
                      'attr' => array('groupclass' => 'hidden-naked', 'style' => 'resize:vertical;',
                                      'aria-label' => $this->translator->trans('Footer Text', array(), null, $this->locale), ),
                      'label' => $this->translator->trans('Footer Text', array(), null, $this->locale), ))
            ->add('i18n_line2', TextareaI18nType::class,
                  array(
                      'required' => false,
                      'attr' => array('groupclass' => 'hidden-naked', 'style' => 'resize:vertical;',
                                      'aria-label' => $this->translator->trans('Headline', array(), null, $this->locale), ),
                      'label' => $this->translator->trans('Headline', array(), null, $this->locale), ))
            ->add('template', ChoiceType::class,
                  array(
                      'choices' => array_flip(array(
                          'date' => $this->translator->trans('date'),
                          'date_all' => $this->translator->trans('date_all'),
                          'date_subserial' => $this->translator->trans('date_subserial'),
                          'place_subserial' => $this->translator->trans('place_subserial'),
                          'subserial' => $this->translator->trans('subserial'),
                          'multisubserial' => $this->translator->trans('multisubserial'),
                      )),
                      'empty_data' => null,
                      'mapped' => false,
                      'attr' => array('groupclass' => 'hidden-naked',
                                      'aria-label' => $this->translator->trans('Template', array(), null, $this->locale), ),
                      'required' => true,
                      'label' => $this->translator->trans('Template', array(), null, $this->locale), ))
            ->add('sorting', ChoiceType::class,
                  array(
                      'choices' => array_flip(Series::$sortText),
                      'empty_data' => null,
                      'attr' => array('groupclass' => 'hidden-naked',
                                      'aria-label' => $this->translator->trans('Sorting', array(), null, $this->locale), ),
                      'required' => true,
                      'label' => $this->translator->trans('Sorting', array(), null, $this->locale), ));

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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pumukit\SchemaBundle\Document\Series',
        ));

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
        $resolver->setRequired('disable_PUDENEW');
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_series';
    }
}
