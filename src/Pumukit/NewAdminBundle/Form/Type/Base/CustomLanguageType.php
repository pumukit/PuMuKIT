<?php

namespace Pumukit\NewAdminBundle\Form\Type\Base;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

class CustomLanguageType extends AbstractType
{
    public static $addonLanguages = array(
      'lse' => 'Spanish Sign Language',
      'ssp' => 'Spanish Sign Language',
      'lsi' => 'International Sign Language',
      'sgn' => 'International Sign Language',
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
        $resolver->setDefaults(array(
                                     'choices' => self::getLanguageNames($this->customLanguages, $this->translator),
                                     ));
    }

    // TODO FIX THIS
    public static function getLanguageNames($customLanguages, $translator)
    {
        $languageNames = Intl::getLanguageBundle()->getLanguageNames();

        if ($customLanguages) {
            $choices = array();
            foreach ($customLanguages as $aux) {
                $code = strtolower($aux);
                $choices[$code] = isset($languageNames[$code]) ?
                  $languageNames[$code] :
                  (isset(self::$addonLanguages[$code]) ? $translator->trans(self::$addonLanguages[$code]) : $code);
            }
        } else {
            $choices = $languageNames;
        }

        return $choices;
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
