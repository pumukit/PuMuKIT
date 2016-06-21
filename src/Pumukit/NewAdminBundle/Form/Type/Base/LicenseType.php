<?php

namespace Pumukit\NewAdminBundle\Form\Type\Base;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

class LicenseType extends AbstractType
{

    private $licenses = array();

    public function __construct(array $licenses = array())
    {
        $this->licenses = $licenses;
    }


    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
                                     'required'  => true,
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
