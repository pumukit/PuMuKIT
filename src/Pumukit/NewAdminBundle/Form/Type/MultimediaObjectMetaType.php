<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Pumukit\NewAdminBundle\Form\Type\Other\Html5dateType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextareaI18nType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nAdvanceType;
use Pumukit\NewAdminBundle\Form\Type\Base\LicenseType;

class MultimediaObjectMetaType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $builder
            ->add('i18n_title', TextI18nType::class,
                  array('required' => false,
                        'attr' => array('aria-label' => $this->translator->trans('Title', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Title', array(), null, $this->locale), ))
            ->add('i18n_subtitle', TextI18nType::class,
                  array('required' => false,
                        'attr' => array('aria-label' => $this->translator->trans('Subtitle', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Subtitle', array(), null, $this->locale), ))
            ->add('i18n_description', TextareaI18nType::class,
                  array('required' => false,
                        'attr' => array('style' => 'resize:vertical;', 'aria-label' => $this->translator->trans('Description', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Description', array(), null, $this->locale), ))
            ->add('comments', TextareaType::class,
                  array('required' => false,
                        'attr' => array('style' => 'resize:vertical;'),
                        'label' => $this->translator->trans('Comments', array(), null, $this->locale), ))
            ->add('i18n_keyword', TextI18nAdvanceType::class,
                  array('required' => false,
                        'attr' => array('class' => 'mmobj materialtags', 'aria-label' => $this->translator->trans('Keywords', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Keywords', array(), null, $this->locale), ))
            ->add('copyright', TextType::class,
                  array('required' => false,
                        'attr' => array('aria-label' => $this->translator->trans('Copyright', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Copyright', array(), null, $this->locale), ))
            ->add('license', LicenseType::class,
                  array('required' => false,
                        'attr' => array('aria-label' => $this->translator->trans('License', array(), null, $this->locale)),
                        'label' => $this->translator->trans('License', array(), null, $this->locale), ))
            ->add('public_date', Html5dateType::class,
                  array('data_class' => 'DateTime',
                        'attr' => array('aria-label' => $this->translator->trans('Publication Date', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Publication Date', array(), null, $this->locale), ))
            ->add('record_date', Html5dateType::class,
                  array('data_class' => 'DateTime',
                        'attr' => array('aria-label' => $this->translator->trans('Recording Date', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Recording Date', array(), null, $this->locale), ))
            ->add('i18n_line2', TextareaI18nType::class,
                  array('required' => false,
                        'attr' => array('groupclass' => 'hidden-naked', 'style' => 'resize:vertical;', 'aria-label' => $this->translator->trans('Headline', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Headline', array(), null, $this->locale), ))
            ->add('subseries', CheckboxType::class,
                  array('mapped' => false,
                        'required' => false,
                        'attr' => array('groupclass' => 'hidden-naked',
                                        'aria-label' => $this->translator->trans('Subseries', array(), null, $this->locale), ),
                        'label' => $this->translator->trans('Subseries', array(), null, $this->locale), ))
            ->add('subseriestitle', TextI18nType::class,
                  array('mapped' => false,
                        'required' => false,
                        'attr' => array('groupclass' => 'hidden-naked', 'aria-label' => $this->translator->trans('Subseries', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Subseries', array(), null, $this->locale), ));

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $multimediaObject = $event->getData();
            $event->getForm()->get('subseries')->setData($multimediaObject->getProperty('subseries'));
            $event->getForm()->get('subseriestitle')->setData($multimediaObject->getProperty('subseriestitle'));
        });

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $subseries = $event->getForm()->get('subseries')->getData();
            $subseriestitle = $event->getForm()->get('subseriestitle')->getData();
            $multimediaObject = $event->getData();
            $multimediaObject->setProperty('subseries', $subseries);
            $multimediaObject->setProperty('subseriestitle', $subseriestitle);
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pumukit\SchemaBundle\Document\MultimediaObject',
        ));

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_mms_meta';
    }
}
