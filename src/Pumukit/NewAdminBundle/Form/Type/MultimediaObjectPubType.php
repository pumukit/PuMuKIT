<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MultimediaObjectPubType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $builder
            ->add(
                'status',
                ChoiceType::class,
                [
                    'choices' => [
                        'Published' => MultimediaObject::STATUS_PUBLISHED,
                        'Blocked' => MultimediaObject::STATUS_BLOCKED,
                        'Hidden' => MultimediaObject::STATUS_HIDDEN,
                    ],
                    'disabled' => $options['not_granted_change_status'],
                    'attr' => ['aria-label' => $this->translator->trans('Status', [], null, $this->locale)],
                    'label' => $this->translator->trans('Status', [], null, $this->locale),
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Pumukit\SchemaBundle\Document\MultimediaObject',
                'not_granted_change_status' => true,
            ]
        );

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_mms_pub';
    }
}
