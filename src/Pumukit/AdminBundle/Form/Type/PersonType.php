<?php

namespace Pumukit\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Pumukit\SchemaBundle\Document\Person;

class PersonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
      ->add('i18n_honorific', 'texti18n',
            array('required' => false, 'label' => 'Honorific'))
      ->add('name', 'text',
        array('attr' => array('style' => 'width: 420px'), 'label' => 'Name'))
      ->add('i18n_post', 'texti18n',
        array('attr' => array('style' => 'width: 420px'), 'required' => false, 'label' => 'Post'))
      ->add('i18n_firm', 'texti18n',
        array('attr' => array('style' => 'width: 420px'), 'required' => false, 'label' => 'Firm'))
      ->add('i18n_bio', 'textareai18n',
        array('attr' => array('style' => 'width: 420px'), 'required' => false, 'label' => 'Bio'))
      ->add('email', 'text',
        array('attr' => array('style' => 'width: 420px'), 'label' => 'Email'))
      ->add('web', 'text',
        array('attr' => array('style' => 'width: 420px'), 'required' => false, 'label' => 'Web'))
      ->add('phone', 'text',
        array('attr' => array('style' => 'width: 420px'), 'required' => false, 'label' => 'Phone'));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
        'data_class' => 'Pumukit\SchemaBundle\Document\Person',
    ));
    }

    public function getName()
    {
        return 'pumukitadmin_person';
    }
}
