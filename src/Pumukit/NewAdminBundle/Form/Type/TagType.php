<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

class TagType extends AbstractType
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
            ->add('metatag', 'checkbox', array('required' => false))
            ->add('display', 'checkbox', array('required' => false))
            ->add('cod', 'text', array(
                                       'attr' => array(
                                                       'pattern' => "^\w*$",
                                                       'oninvalid' => "setCustomValidity('The code can not have blank spaces neither special characters')",
                                                       'oninput' => "setCustomValidity('')"),
                                       'label' => $this->translator->trans('Cod', array(), null, $this->locale)))
            ->add('i18n_title', 'texti18n',
                  array('label' => $this->translator->trans('Title', array(), null, $this->locale)))
            ->add('i18n_description', 'textareai18n',
                  array(
                        'required' => false,
                        'attr' => array('style' => 'resize:vertical;'),
                        'label' => $this->translator->trans('Description', array(), null, $this->locale)));


        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
            $tag = $event->getData();

            $fields = $tag->getProperty("customfield");
            foreach(array_filter(preg_split('/[,\s]+/', $fields)) as $field) {
                $auxField = explode(":", $field);
                $formOptions = array('mapped' => false, 'required' => false, 'data' => $tag->getProperty($auxField[0]));
                
                try {
                    $event->getForm()->add($auxField[0], isset($auxField[1])?$auxField[1]:'text', $formOptions);
                } catch(\InvalidArgumentException $e) {
                    $event->getForm()->add($auxField[0], 'text', $formOptions);
                }
            }
        });

        
        $builder->addEventListener(FormEvents::SUBMIT, function(FormEvent $event) {
            $tag = $event->getData();

            $fields = $tag->getProperty("customfield");
            foreach(array_filter(preg_split('/[,\s]+/', $fields)) as $field) {
                $auxField = explode(":", $field);
                $data = $event->getForm()->get($auxField[0])->getData();
                $tag->setProperty($auxField[0], $data);
            }
        });
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
        'data_class' => 'Pumukit\SchemaBundle\Document\Tag',
    ));
    }

    public function getName()
    {
        return 'pumukitnewadmin_tag';
    }
}
