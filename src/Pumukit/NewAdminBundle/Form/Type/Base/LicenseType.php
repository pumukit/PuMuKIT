<?php

namespace Pumukit\NewAdminBundle\Form\Type\Base;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class LicenseType extends AbstractType
{
    private $licenses = array();

    /**
     * @param array $licenses: list of licenses, if is a sequential array use text as value and label.
     */
    public function __construct(array $licenses = array())
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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
                                     'required' => true,
                                     'choices' => $this->licenses,
                                     ));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        if (0 == count($this->licenses)) {
            return 'text';
        }

        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'license';
    }
}
