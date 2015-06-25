<?php

namespace Pumukit\Cmar\WebTVBundle\Twig;

use Symfony\Component\Intl\Intl;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\Tag;
use Doctrine\ODM\MongoDB\DocumentManager;

class CmarWebTVExtension extends \Twig_Extension
{
    private $dm;
    private $languages;

    /**
     * Constructor
     */
    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
        $this->languages = Intl::getLanguageBundle()->getLanguageNames();
    }
  
    /**
     * Get name
     */
    public function getName()
    {
        return 'pumukit_cmar_web_tv_extension';
    }

    /**
     * Get filters
     */
    public function getFilters()
    {
        return array(
                     new \Twig_SimpleFilter('language_name', array($this, 'getLanguageName')),
                     new \Twig_SimpleFilter('count_multimedia_objects', array($this, 'countMultimediaObjects')),
                     );
    }

    /**
     * Get functions
     */
    function getFunctions()
    {
      return array(
                   new \Twig_SimpleFunction('iframeurl', array($this, 'getIframeUrl')),
                   new \Twig_SimpleFunction('precinct_complete_name', array($this, 'getPrecinctCompleteName')),
                   );
    }

    /**
     * Get language name
     *
     * @param string $code
     * @return string
     */
    public function getLanguageName($code)
    {
        return ucfirst($this->languages[$code]);
    }

    /**
     * Get Iframe URL
     *
     * @return string
     */
    public function getIframeUrl($multimediaObject, $isHTML5=false, $isDownloadable=false)
    {
        $opencastTrack = $multimediaObject->getTrackWithTag('opencast');

        $url = str_replace('%id%', $multimediaObject->getProperty('opencast'), $multimediaObject->getProperty('opencasturl'));

        $broadcast_type = $multimediaObject->getBroadcast()->getBroadcastTypeId();
        if (Broadcast::BROADCAST_TYPE_PUB == $broadcast_type) {
            $url_player = '/cmarwatch.html';
        } else {
            $url_player = '/securitywatch.html';
        }
        $url = str_replace('/watch.html', $url_player, $url);

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

    /**
     * Count Multimedia Objects
     *
     * @param Series $series
     * @return integer
     */
    public function countMultimediaObjects($series)
    {
        return $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject')->countInSeries($series);
    }

    /**
     * Get precinct complete name
     *
     * @param Tag|EmbeddedTag $precinctTag
     * @param string $locale
     * @return string
     */
    public function getPrecinctCompleteName($precinctTag, $locale)
    {
        if (!($precinctTag instanceof Tag)) {
            $precinctTag = $this->dm->getRepository('PumukitSchemaBundle:Tag')->findOneByCod($precinctTag->getCod());
        }
        $placeTag = $precinctTag->getParent();
        $address = '';
        $i18nAddress = $placeTag->getProperty("address");
        if ($i18nAddress && (!empty(array_filter($i18nAddress)))) {
            $address = ($i18nAddress[$locale] == '')?'':' - '.$i18nAddress[$locale];
        }
        $precinct = ($precinctTag->getTitle() == '')?'':$precinctTag->getTitle().', ';
        return $precinct . $placeTag->getTitle().$address;
    }
}