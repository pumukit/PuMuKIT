<?php

namespace Pumukit\TranscoBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CpuType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
      //$this->add(new TextField('subject', array('max_length' => 100)));
      //$this->add(new TextareaField('message'));
      //$this->add(new TextField('sender'));
      //$this->add(new CheckboxField('ccmyself', array('required' => false)));

        $builder
              ->add('IP')
              ->add('endpoint')
              ->add('so_type')
              ->add('num_jobs')
              ->add('max_jobs')
              ->add('login')
              ->add('passwd')
          ;
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pumukit\TranscoBundle\Entity\Cpu',
            'csrf_protection' => false,
        ));
    }
    
    public function getName()
    {
        return 'pumukit_transcobundle_cputype';
    }

}