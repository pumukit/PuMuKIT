<?php

namespace Pumukit\Legacy\WebTVBundle\Twig;

use Symfony\Component\Routing\RequestContext;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\MaterialService;
use Pumukit\SchemaBundle\Services\PicService;
use Doctrine\ODM\MongoDB\DocumentManager;

class PumukitExtension extends \Twig_Extension
{
    /**
     * @var string
     */
    protected $defaultPic;

    /**
     * @var RequestContext
     */
    protected $context;

    private $dm;
    private $materialService;
    private $picService;

    public function __construct(DocumentManager $documentManager, RequestContext $context, $defaultPic, MaterialService $materialService, PicService $picService)
    {
        $this->dm = $documentManager;
        $this->context = $context;
        $this->defaultPic = $defaultPic;
        $this->materialService = $materialService;
        $this->picService = $picService;
    }

    public function getName()
    {
        return 'pumukit_extension';
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('first_url_pic', array($this, 'getFirstUrlPicFilter')),
            new \Twig_SimpleFilter('precinct_fulltitle', array($this, 'getPrecinctFulltitle')),
            new \Twig_SimpleFilter('duration_minutes_seconds', array($this, 'getDurationInMinutesSeconds')),
        );
    }

    /**
     * Get functions
     */
    public function getFunctions()
    {
        return array(
                     new \Twig_SimpleFunction('public_broadcast', array($this, 'getPublicBroadcast')),
                     new \Twig_SimpleFunction('precinct', array($this, 'getPrecinct')),
                     new \Twig_SimpleFunction('precinct_of_series', array($this, 'getPrecinctOfSeries')),
                     new \Twig_SimpleFunction('captions', array($this, 'getCaptions')),
                     );
    }

    /**
     *
     * @param Series|MultimediaObject $object    Object to get the url (using $object->getPics())
     * @param boolean                 $absolute  return absolute path.
     * @param boolean                 $hd        return HD image.
     *
     * @return string
     */
    public function getFirstUrlPicFilter($object, $absolute=false, $hd=true)
    {
        return $this->picService->getFirstUrlPic($object, $absolute, $hd);
    }

    /**
     * Get public broadcast
     *
     * @return string
     */
    public function getPublicBroadcast()
    {
        return Broadcast::BROADCAST_TYPE_PUB;
    }

    /**
     * Get precinct
     *
     * @param ArrayCollection $embeddedTags
     * @return EmbbededTag|null
     */
    public function getPrecinct($embeddedTags)
    {
        $precinctTag = null;

        foreach ($embeddedTags as $tag) {
            if ((0 === strpos($tag->getCod(), 'PLACE')) && (0 < strpos($tag->getCod(), 'PRECINCT'))) {
                return $tag;
            }
        }

        return $precinctTag;
    }

    /**
     * Get precinct of Series
     *
     * @param ArrayCollection $multimediaObjects
     * @return EmbbededTag|null
     */
    public function getPrecinctOfSeries($multimediaObjects)
    {
        $precinctTag = false;
        $precinctCode = null;
        $first = true;
        foreach ($multimediaObjects as $multimediaObject) {
            if ($first) {
                $precinctTag = $this->getPrecinct($multimediaObject->getTags());
                if (!$precinctTag) {
                    return false;
                }
                $precinctCode = $precinctTag->getCod();
                $first = false;
            } else {
                $precinctTag = $this->getPrecinct($multimediaObject->getTags());
                if (!$precinctTag) {
                    return false;
                }
                if ($precinctCode != $precinctTag->getCod()) {
                    return false;
                }
            }
        }

        return $precinctTag;
    }

    /**
     * Get precinct fulltitle
     *
     * @param EmbbededTag $precinctEmbeddedTag
     * @return string
     */
    public function getPrecinctFulltitle($precinctEmbeddedTag)
    {
        $fulltitle = '';

        if ($precinctEmbeddedTag) {
            $tagRepo = $this->dm->getRepository('PumukitSchemaBundle:Tag');
            $precinctTag = $tagRepo->findOneByCod($precinctEmbeddedTag->getCod());
            if ($precinctTag) {
                if ($precinctTag->getTitle()) {
                    $fulltitle = $precinctTag->getTitle();
                }
                $placeTag = $precinctTag->getParent();
                if ($placeTag) {
                    if ($placeTag->getTitle()) {
                        if ($fulltitle) {
                            $fulltitle .= ', '.$placeTag->getTitle();
                        } else {
                            $fulltitle = $placeTag->getTitle();
                        }
                    }
                }
            } elseif ($precinctEmbeddedTag->getTitle()) {
                $fulltitle = $precinctEmbeddedTag->getTitle();
            }
        }

        return $fulltitle;
    }

    /**
     * Get duration in minutes and seconds
     *
     * @param int $duration
     * @return string
     */
    public function getDurationInMinutesSeconds($duration)
    {
        $minutes = floor($duration / 60);

        $seconds = $duration % 60;
        if ($seconds < 10) {
            $seconds = '0'.$seconds;
        }

        return $minutes."' ".$seconds."''";
    }

    /**
     * Get captions
     *
     * @param MultimediaObject $multimediaObject
     * @return ArrayCollection
     */
    public function getCaptions(MultimediaObject $multimediaObject)
    {
        return $this->materialService->getCaptions($multimediaObject);
    }
}
