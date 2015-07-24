<?php

namespace Pumukit\NewAdminBundle\Form\Type\Base;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

class CustomLanguageType extends AbstractType
{
    protected static $addonLanguages = array(
      'lse' => 'Spanish Sign Language',
      'ssp' => 'Spanish Sign Language',
      'lsi' => 'Sign Language',
      'sgn' => 'Sign Language'
    );
    
    private $translator;
    private $customLanguages;

    public function __construct(TranslatorInterface $translator, array $customLanguages = array())
    {
        $this->translator = $translator;
        $this->customLanguages = $customLanguages;
    }


    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $languageNames = Intl::getLanguageBundle()->getLanguageNames();

        if($this->customLanguages) {
          $choices = array();
          foreach($this->customLanguages as $aux) {
              $code = strtolower($aux);
              $choices[$code] = isset($languageNames[$code]) ? 
                $languageNames[$code] : 
                (isset(self::$addonLanguages[$code]) ? $this->translator->trans(self::$addonLanguages[$code]) : $code);
          }
        } else {
          $choices = $languageNames;
        }

        $resolver->setDefaults(array(
            'choices' => $choices,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'customlanguage';
    }
}
