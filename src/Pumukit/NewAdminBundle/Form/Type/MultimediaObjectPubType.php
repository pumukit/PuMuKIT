<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\Translation\TranslatorInterface;

class MultimediaObjectPubType extends AbstractType
{
    private $translator;
    private $locale;

    public function __construct(TranslatorInterface $translator, $locale='en')
    {
        $this->translator = $translator;
        $this->locale = $locale;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('status', 'choice',
                  array('choices' => array(
                                           MultimediaObject::STATUS_PUBLISHED => 'Published',
                                           MultimediaObject::STATUS_BLOQ => 'Blocked',
                                           MultimediaObject::STATUS_HIDE => 'Hidden'
                                           ),
                        'disabled' => $options['not_admin'],
                        'label' => $this->translator->trans('Status', array(), null, $this->locale) ))
            ->add('broadcast', null, array('label' => $this->translator->trans('Broadcast', array(), null, $this->locale)));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
        'data_class' => 'Pumukit\SchemaBundle\Document\MultimediaObject',
        'not_admin' => true,
                                     ));
    }

    public function getName()
    {
        return 'pumukitnewadmin_mms_pub';
    }
}
