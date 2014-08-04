<?php

    namespace Pumukit\SchemaBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MultimediaObjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('translations', 'a2lix_translations_gedmo', array(
                'label' => false,
                'translatable_class' => "Pumukit\SchemaBundle\Entity\MultimediaObject",
                'locales' => array('gl', 'es'),   // [optional|required - depends on the presence in config.yml] See above
                'required' => false,              // [optional] Overrides default_required if need
            ))
            ->add('rank')
            ->add('status')
            ->add('record_date')
            ->add('public_date')
            ->add('subtitle')
            ->add('description')
            ->add('copyright')
            ->add('keyword')
            ->add('duration')
            ->add('series')
            ->add('tags');
        /*
            ->add('title', 'translatable_field', array(
                'field'          => 'title',
                'property_path'  => 'translations'
            ))
        ;*/
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pumukit\SchemaBundle\Entity\MultimediaObject'
        ));
    }

    public function getName()
    {
        return 'pumukit_schemabundle_multimediaobjecttype';
    }
}
