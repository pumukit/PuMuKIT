<?php

namespace Pumukit\LegacyWebTVBundle\Twig;

use Pumukit\SchemaBundle\Document\Series;
use Symfony\Component\Routing\RequestContext;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\CaptionService;
use Pumukit\SchemaBundle\Services\PicService;
use Pumukit\LegacyWebTVBundle\Services\LinkService;
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
    private $captionService;
    private $picService;
    private $linkService;
    private $mmobjDurationService;

    public function __construct(DocumentManager $documentManager, RequestContext $context, $defaultPic, CaptionService $captionService, PicService $picService, LinkService $linkService, $mmobjDurationService)
    {
        $this->dm = $documentManager;
        $this->context = $context;
        $this->defaultPic = $defaultPic;
        $this->captionService = $captionService;
        $this->picService = $picService;
        $this->linkService = $linkService;
        $this->mmobjDurationService = $mmobjDurationService;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('first_url_pic', array($this, 'getFirstUrlPicFilter')),
            new \Twig_SimpleFilter('precinct_fulltitle', array($this, 'getPrecinctFulltitle')),
            new \Twig_SimpleFilter('duration_minutes_seconds', array($this, 'getDurationInMinutesSeconds')),
            new \Twig_SimpleFilter('duration_string', array($this, 'getDurationString')),
        );
    }

    /**
     * Get functions.
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('public_broadcast', array($this, 'getPublicBroadcast')),
            new \Twig_SimpleFunction('precinct', array($this, 'getPrecinct')),
            new \Twig_SimpleFunction('precinct_of_series', array($this, 'getPrecinctOfSeries')),
            new \Twig_SimpleFunction('captions', array($this, 'getCaptions')),
            new \Twig_SimpleFunction('iframeurl', array($this, 'getIframeUrl')),
            new \Twig_SimpleFunction('path_to_tag', array($this, 'getPathToTag')),
            new \Twig_SimpleFunction('mmobj_duration', array($this, 'getMmobjDuration')),
            new \Twig_SimpleFunction('next_event_session', array($this, 'getNextEventSession')),
            new \Twig_SimpleFunction('live_event_session', array($this, 'getLiveEventSession')),
            new \Twig_SimpleFunction('precinct_of_mmo', array($this, 'getPrecinctOfMultimediaObject')),
            new \Twig_SimpleFunction('count_published_mmobjs', array($this, 'getMMobjsFromSerie')),
        );
    }

    /**
     * @param Series|MultimediaObject $object   Object to get the url (using $object->getPics())
     * @param bool                    $absolute return absolute path
     * @param bool                    $hd       return HD image
     *
     * @return string
     */
    public function getFirstUrlPicFilter($object, $absolute = false, $hd = false)
    {
        return $this->picService->getFirstUrlPic($object, $absolute, $hd);
    }

    /**
     * Get public broadcast.
     *
     * @return string
     */
    public function getPublicBroadcast()
    {
        return EmbeddedBroadcast::TYPE_PUBLIC;
    }

    /**
     * Get precinct.
     *
     * @param $embeddedTags
     *
     * @return mixed
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
     * Get precinct of Series.
     *
     * @param $multimediaObjects
     *
     * @return bool|mixed
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
     * Get precinct of Series.
     *
     * @param $multimediaObject
     *
     * @return mixed
     */
    public function getPrecinctOfMultimediaObject($multimediaObject)
    {
        $precinctTag = $this->getPrecinct($multimediaObject->getTags());

        return $precinctTag;
    }

    /**
     * Get precinct full title.
     *
     * @param $precinctEmbeddedTag
     *
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
     * Get duration in minutes and seconds.
     *
     * @param int $duration
     *
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
     * Get duration as uninternationalized string
     * The format is type 78'12''.
     *
     * @param int $duration
     *
     * @return string
     */
    public function getDurationString($duration)
    {
        if ($duration > 0) {
            $min = floor($duration / 60);
            $seg = $duration % 60;

            if ($seg < 10) {
                $seg = '0'.$seg;
            }

            if (0 == $min) {
                $aux = $seg."''";
            } else {
                $aux = $min."' ".$seg."''";
            }

            return $aux;
        } else {
            return "0''";
        }
    }

    /**
     * Wrapper for the duration of the object. Gets the duration using the MultimediaObjectDurationService.
     *
     * @param MultimediaObject $mmobj
     *
     * @return int
     */
    public function getMmobjDuration(MultimediaObject $mmobj)
    {
        return $this->mmobjDurationService->getMmobjDuration($mmobj);
    }

    /**
     * Get captions.
     *
     * @param MultimediaObject $multimediaObject
     *
     * @return array
     */
    public function getCaptions(MultimediaObject $multimediaObject)
    {
        return $this->captionService->getCaptions($multimediaObject);
    }

    /**
     * Get Iframe Url of a Multimedia Object.
     *
     * @param MultimediaObject $multimediaObject
     * @param bool             $isHTML5          default=false
     * @param bool             $isDownloadable   default=false
     *
     * @return mixed|string
     */
    public function getIframeUrl($multimediaObject, $isHTML5 = false, $isDownloadable = false)
    {
        $url = str_replace('%id%', $multimediaObject->getProperty('opencast'), $multimediaObject->getProperty('opencasturl'));

        $embeddedBroadcast = $multimediaObject->getEmbeddedBroadcast();
        if (!$embeddedBroadcast) {
            $url_player = '/cmarwatch.html';
        } elseif (EmbeddedBroadcast::TYPE_PUBLIC == $embeddedBroadcast->getType()) {
            $url_player = '/cmarwatch.html';
        } else {
            $url_player = '/securitywatch.html';
        }
        $url = str_replace('/watch.html', $url_player, $url);

        if ($isHTML5) {
            $url = str_replace('/engage/ui/', '/paellaengage/ui/', $url);
        }

        if ($isDownloadable) {
            $url = $url.'&videomode=progressive';
        }

        $invert = $multimediaObject->getProperty('opencastinvert');
        if ($invert && $isHTML5) {
            $url = $url.'&display=invert';
        }

        return $url;
    }

    public function getPathToTag($tagCod = null, $useBlockedTagAsGeneral = null, $parameters = array(), $relative = false)
    {
        return $this->linkService->generatePathToTag($tagCod, $useBlockedTagAsGeneral);
    }

    /**
     * Get next event session without sessions that reproducing now.
     *
     * @param $event
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getNextEventSession($event)
    {
        $embeddedEventSession = $event['embeddedEventSession'];

        $now = new \DateTime();

        $firstSession = '';
        foreach ($embeddedEventSession as $session) {
            if ($now < $session['start']) {
                $now->add(new \DateInterval('PT'.$session['duration'].'S'));
                if ($now < $session['start']) {
                    $firstSession = $session['start'];
                    break;
                }
            }
        }

        return $firstSession;
    }

    /**
     * Get next live event session.
     *
     * @param $multimediaObject
     *
     * @return object
     *
     * @throws \Exception
     */
    public function getLiveEventSession($multimediaObject)
    {
        $now = new \DateTime();

        $sessionData = '';
        foreach ($multimediaObject->getEmbeddedEvent()->getEmbeddedEventSession() as $session) {
            if ($now > $session->getStart()) {
                $sessionEnd = clone $session->getStart();
                $sessionEnd->add(new \DateInterval('PT'.$session->getDuration().'S'));
                if ($now < $sessionEnd) {
                    $sessionData = $session;
                    break;
                }
            } elseif ($now < $session->getStart()) {
                $sessionData = $session;
                break;
            }
        }

        return $sessionData;
    }

    /**
     * @param Series $series
     *
     * @return int
     */
    public function getMMobjsFromSerie($series)
    {
        $criteria = array(
            'series' => new \MongoId($series),
            'status' => MultimediaObject::STATUS_PUBLISHED,
            'tags.cod' => 'PUCHWEBTV',
            'islive' => false,
        );

        $multimediaObjects = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findBy($criteria);

        return count($multimediaObjects);
    }
}
