<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Pumukit\LiveBundle\Document\Live;
use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextareaI18nType;

/**
 * Class LiveType.
 */
class LiveType extends AbstractType
{
    private $translator;
    private $locale;

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $builder
            ->add('i18n_name', TextI18nType::class,
                  array(
                      'attr' => array('aria-label' => $this->translator->trans('Name', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Name', array(), null, $this->locale), ))
            ->add('i18n_description', TextareaI18nType::class,
                  array(
                      'required' => false,
                      'attr' => array('style' => 'resize:vertical;', 'aria-label' => $this->translator->trans('Description', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Description', array(), null, $this->locale), ))
            ->add('url', UrlType::class,
                  array(
                      'attr' => array('aria-label' => $this->translator->trans('URL', array(), null, $this->locale)),
                      'label' => $this->translator->trans('URL', array(), null, $this->locale), ))
            ->add('source_name', TextType::class,
                  array(
                      'attr' => array('aria-label' => $this->translator->trans('STREAM', array(), null, $this->locale)),
                      'label' => $this->translator->trans('STREAM', array(), null, $this->locale), ))
            ->add('passwd', TextType::class,
                  array(
                      'required' => false,
                      'attr' => array('aria-label' => $this->translator->trans('Password', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Password', array(), null, $this->locale), ))
            ->add('broadcasting', ChoiceType::class,
                  array(
                      'choices' => array('On hold' => '0', 'Live Broadcasting' => '1'),
                      'attr' => array('aria-label' => $this->translator->trans('Status', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Status', array(), null, $this->locale), ))
            ->add('live_type', ChoiceType::class,
                  array(
                      'attr' => array('aria-label' => $this->translator->trans('Technology', array(), null, $this->locale)),
                      'choices' => array(
                          'WOWZA' => Live::LIVE_TYPE_WOWZA,
                          'Adobe Media Server' => Live::LIVE_TYPE_AMS,
                          'FMS (deprecated use WOWZA or AMS)' => Live::LIVE_TYPE_FMS,
                          'WMS (deprecated)' => Live::LIVE_TYPE_WMS,
                      ),
                      'label' => $this->translator->trans('Technology', array(), null, $this->locale), ))
            ->add('chat', CheckboxType::class,
                array(
                    'required' => false,
                    'attr' => array('aria-label' => $this->translator->trans('Enable chat on this channel\'s page', array(), null, $this->locale)),
                    'label' => $this->translator->trans('Enable chat on this channel\'s page', array(), null, $this->locale),
                ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pumukit\LiveBundle\Document\Live',
        ));

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_live';
    }
}
