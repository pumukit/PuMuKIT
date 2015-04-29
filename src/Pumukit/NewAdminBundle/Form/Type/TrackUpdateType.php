<?php
namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Pumukit\NewAdminBundle\Form\Type\Other\TrackresolutionType;
use Pumukit\NewAdminBundle\Form\Type\Other\TrackdurationType;
use Symfony\Component\Translation\TranslatorInterface;

class TrackUpdateType extends AbstractType
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
            ->add('i18n_description', 'texti18n',
                  array(
                        'required' => false,
                        'label' => $this->translator->trans('Description', array(), null, $this->locale)))
            ->add('hide', 'checkbox',
                  array(
                        'required' => false,
                        'label' => $this->translator->trans('Hide', array(), null, $this->locale)))
            ->add('language', 'language',
                  array(
                        'required' => false,
                        'label' => $this->translator->trans('Language', array(), null, $this->locale)))
          ->add('durationinminutesandseconds', new TrackdurationType(),
                array(
                      'required' => true,
                      'read_only' => true,
                      'label' => $this->translator->trans('Duration', array(), null, $this->locale)))
          ->add('resolution', new TrackresolutionType(),
                array(
                      'required' => true,
                      'read_only' => true,
                      'label' => $this->translator->trans('Resolution', array(), null, $this->locale)))
          ->add('size', 'integer',
                array(
                      'required' => true,
                      'read_only' => true,
                      'label' => $this->translator->trans('Size', array(), null, $this->locale)))
          ->add('path', 'text',
                array(
                      'required' => true,
                      'read_only' => true,
                      'label' => $this->translator->trans('File', array(), null, $this->locale)))
          ->add('url', 'url',
                array(
                      'required' => true,
                      'read_only' => true,
                      'label' => $this->translator->trans('URL', array(), null, $this->locale)));
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
                                     'data_class' => 'Pumukit\SchemaBundle\Document\Track',
                                     ));
    }
    
    public function getName()
    {
        return 'pumukitnewadmin_track_update';
    }
}