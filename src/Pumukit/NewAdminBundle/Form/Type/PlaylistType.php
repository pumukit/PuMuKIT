<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextareaI18nType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nAdvanceType;

class PlaylistType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $builder
            ->add('i18n_title', TextI18nType::class,
                  array(
                      'attr' => array('aria-label' => $this->translator->trans('Title', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Title', array(), null, $this->locale), ))
            ->add('i18n_description', TextareaI18nType::class,
                  array(
                      'required' => false,
                      'attr' => array('style' => 'resize:vertical;', 'aria-label' => $this->translator->trans('Description', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Description', array(), null, $this->locale), ))
            ->add('i18n_keyword', TextI18nAdvanceType::class,
                  array(
                      'required' => false,
                      'attr' => array('aria-label' => $this->translator->trans('Keywords', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Keywords', array(), null, $this->locale), ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pumukit\SchemaBundle\Document\Series',
        ));

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_series';
    }
}
