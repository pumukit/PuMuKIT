<?php

namespace Pumukit\TemplateBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TemplateType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('hide', null,
                  array(
                      'required' => false,
                  )
            )
            ->add('name')
            ->add('i18n_text', 'textareai18n',
                  array(
                      'attr' => array('style' => 'height: 200px;'),
                      'label' => 'Text',
                  )
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pumukit\TemplateBundle\Document\Template',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pumukittemplate_template';
    }
}
