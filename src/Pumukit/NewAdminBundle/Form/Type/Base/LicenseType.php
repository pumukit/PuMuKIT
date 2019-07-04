<?php

namespace Pumukit\NewAdminBundle\Form\Type\Base;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LicenseType extends AbstractType
{
    private $licenses = [];

    /**
     * @param array $licenses : list of licenses, if is a sequential array use text as value and label
     */
    public function __construct(array $licenses = [])
    {
        if (array_keys($licenses) !== range(0, count($licenses) - 1)) {
            //is associative
            $this->licenses = $licenses;
        } else {
            //is sequential
            foreach ($licenses as $l) {
                $this->licenses[$l] = $l;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'required' => true,
                'choices' => $this->licenses,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        if (0 == count($this->licenses)) {
            return TextType::class;
        }

        return ChoiceType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'license';
    }
}
