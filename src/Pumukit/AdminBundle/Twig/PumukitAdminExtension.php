<?php

namespace Pumukit\AdminBundle\Twig;

use Symfony\Component\Intl\Intl;

class PumukitAdminExtension extends \Twig_Extension
{
    private $languages;

    /**
     * Constructor
     */
    public function __construct()
    {
      $this->languages = Intl::getLanguageBundle()->getLanguageNames();
    }
  
    /**
     * Get name
     */
    public function getName()
    {
        return 'pumukitadmin_extension';
    }

     /**
     * Get filters
     */
    public function getFilters()
    {
        return array(
                     new \Twig_SimpleFilter('basename', array($this, 'getBasename')),
                     new \Twig_SimpleFilter('profile', array($this, 'getProfile')),
                     new \Twig_SimpleFilter('duration_string', array($this, 'getDurationString')),
                     new \Twig_SimpleFilter('language_name', array($this, 'getLanguageName')),
                     );
    }

    /**
     * Get basename
     * 
     * @param string $path
     * @return string
     */
    public function getBasename($path)
    {
        return basename($path);
    }

    /**
     * Get profile
     *
     * @param Collection $tags
     * @return string
     */
    public function getProfile($tags)
    {
        $profile = '';

        foreach($tags as $tag){
            if (false !== strpos($tag, 'profile:' )) {
                return substr($tag, strlen('profile:'), strlen($tag)-1);
            }
        }

        return $profile;
    }

    /**
     * Get duration string
     *
     * @param int $duration
     * @return string
     */
    public function getDurationString($duration)
    {
        $min = $this->getDurationInMinutes($duration);
        if ($min == 0 ) $aux = $this->getDurationInSeconds($duration) ."''";
        else $aux = $min . "' ". $this->getDurationInSeconds($duration) ."''";
        
        return $aux;
    }

    /**
     * Get language name
     *
     * @param string $code
     * @return string
     */
    public function getLanguageName($code)
    {
        return $this->languages[$code];
    }

    /**
     * Get duration in minutes
     * Returns duration file in minutes
     *
     * @return integer minutes
     */
    private function getDurationInMinutes($duration)
    {
        return floor($duration / 60);
    }

    /**
     * Get duration in seconds
     * Returns duration file in seconds
     *
     * @return integer seconds
     */
    private function getDurationInSeconds($duration)
    {
        $aux = $duration % 60;
        if ($aux < 10 ) $aux = '0' . $aux;
        
        return $aux;
    }
}