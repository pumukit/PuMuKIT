<?php

namespace Pumukit\NewAdminBundle\Form\Type\Base;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class CustomLanguageType extends AbstractType
{
    public static $addonLanguages = [
        'lse' => 'Spanish Sign Language',
        'ssp' => 'Spanish Sign Language',
        'lsi' => 'International Sign Language',
        'sgn' => 'International Sign Language',
    ];

    private $translator;
    private $customLanguages;

    public function __construct(TranslatorInterface $translator, array $customLanguages = [])
    {
        $this->translator = $translator;
        $this->customLanguages = $customLanguages;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => array_flip(self::getLanguageNames($this->customLanguages, $this->translator)),
            ]
        );
    }

    public static function getLanguageNames($customLanguages, $translator)
    {
        $languageNames = Intl::getLanguageBundle()->getLanguageNames();

        if ($customLanguages) {
            $choices = [];
            foreach ($customLanguages as $aux) {
                $code = strtolower($aux);
                $choices[$code] = $languageNames[$code] ??
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
        return ChoiceType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'customlanguage';
    }
}
