<?php

namespace Pumukit\MoodleBundle\Twig;

class PumukitMoodleExtension extends \Twig_Extension
{
    /**
     * Get name
     */
    public function getName()
    {
        return 'pumukit_moodle_extension';
    }

    /**
     * Get functions
     */
    function getFunctions()
    {
        return array(
                     new \Twig_SimpleFunction('iframeurl', array($this, 'getIframeUrl')),
                     );
    }

    /**
     * Get Iframe URL
     *
     * @return string
     */
    public function getIframeUrl($multimediaObject, $isHTML5=false, $isDownloadable=false)
    {
        $url = str_replace('%id%', $multimediaObject->getProperty('opencast'), $multimediaObject->getProperty('opencasturl'));

        if ($isHTML5) {
            $url = str_replace('/engage/ui/', '/paellaengage/ui/', $url);
        }

        if ($isDownloadable) {
          $url = $url . "&videomode=progressive";
        }

        $invert = $multimediaObject->getProperty('opencastinvert');
        if ($invert && $isHTML5) {
            $url = $url . "&display=invert";
        }

        return $url;
    }

}
