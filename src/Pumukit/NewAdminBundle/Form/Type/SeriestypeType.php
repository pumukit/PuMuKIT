<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextareaI18nType;

class SeriestypeType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $builder
            ->add('cod', TextType::class,
                  array('required' => true,
                        'attr' => array('aria-label' => $this->translator->trans('Code', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Code', array(), null, $this->locale), ))
            ->add('i18n_name', TextI18nType::class,
                  array('required' => true,
                        'attr' => array('style' => 'resize:vertical;', 'aria-label' => $this->translator->trans('Name', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Name', array(), null, $this->locale), ))
            ->add('i18n_description', TextareaI18nType::class,
                  array('required' => false,
                        'attr' => array('aria-label' => $this->translator->trans('Description', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Description', array(), null, $this->locale), ))
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pumukit\SchemaBundle\Document\SeriesType',
        ));

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_seriestype';
    }
}
