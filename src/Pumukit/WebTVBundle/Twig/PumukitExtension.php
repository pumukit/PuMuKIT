<?php

namespace Pumukit\WebTVBundle\Twig;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\EmbeddedTag;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Services\CaptionService;
use Pumukit\SchemaBundle\Services\MultimediaObjectDurationService;
use Pumukit\SchemaBundle\Services\PicService;
use Pumukit\WebTVBundle\Services\LinkService;
use Symfony\Component\Routing\RequestContext;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Class PumukitExtension.
 */
class PumukitExtension extends AbstractExtension
{
    protected $defaultPic;

    /**
     * @var RequestContext
     */
    protected $context;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var CaptionService
     */
    private $captionService;

    /**
     * @var PicService
     */
    private $picService;

    /**
     * @var LinkService
     */
    private $linkService;

    private $mmobjDurationService;

    public function __construct(DocumentManager $documentManager, RequestContext $context, $defaultPic, CaptionService $captionService, PicService $picService, LinkService $linkService, MultimediaObjectDurationService $mmobjDurationService)
    {
        $this->dm = $documentManager;
        $this->context = $context;
        $this->defaultPic = $defaultPic;
        $this->captionService = $captionService;
        $this->picService = $picService;
        $this->linkService = $linkService;
        $this->mmobjDurationService = $mmobjDurationService;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return [
            new TwigFilter('first_url_pic', [$this, 'getFirstUrlPicFilter']),
            new TwigFilter('precinct_fulltitle', [$this, 'getPrecinctFullTitle']),
            new TwigFilter('duration_minutes_seconds', [$this, 'getDurationInMinutesSeconds']),
            new TwigFilter('duration_string', [$this, 'getDurationString']),
            new TwigFilter('first_dynamic_pic', [$this, 'getDynamicPic']),
        ];
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('public_broadcast', [$this, 'getPublicBroadcast']),
            new TwigFunction('precinct', [$this, 'getPrecinct']),
            new TwigFunction('precinct_of_series', [$this, 'getPrecinctOfSeries']),
            new TwigFunction('captions', [$this, 'getCaptions']),
            new TwigFunction('iframeurl', [$this, 'getIframeUrl']),
            new TwigFunction('path_to_tag', [$this, 'getPathToTag']),
            new TwigFunction('mmobj_duration', [$this, 'getMmobjDuration']),
            new TwigFunction('next_event_session', [$this, 'getNextEventSession']),
            new TwigFunction('live_event_session', [$this, 'getLiveEventSession']),
            new TwigFunction('precinct_of_mmo', [$this, 'getPrecinctOfMultimediaObject']),
            new TwigFunction('count_published_mmobjs', [$this, 'getMMobjsFromSerie']),
        ];
    }

    /**
     * @param MultimediaObject|Series $object   Object to get the url (using $object->getPics())
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
     * @param mixed $object
     * @param bool  $absolute
     *
     * @return string|null
     */
    public function getDynamicPic($object, $absolute = false)
    {
        return $this->picService->getDynamicPic($object, $absolute);
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
     * @param array $embeddedTags
     *
     * @return EmbeddedTag|null
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
     * @param array $multimediaObjects
     *
     * @return bool|EmbeddedTag
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
     * @param MultimediaObject $multimediaObject
     *
     * @return EmbeddedTag|null
     */
    public function getPrecinctOfMultimediaObject($multimediaObject)
    {
        return $this->getPrecinct($multimediaObject->getTags());
    }

    /**
     * Get precinct full title.
     *
     * @param EmbeddedTag $precinctEmbeddedTag
     *
     * @return string
     */
    public function getPrecinctFullTitle($precinctEmbeddedTag): string
    {
        $fullTitle = '';

        $precinctTag = $this->dm->getRepository(Tag::class)->findOneBy(['cod' => $precinctEmbeddedTag->getCod()]);
        if ($precinctTag) {
            if ($precinctTag->getTitle()) {
                $fullTitle = $precinctTag->getTitle();
            }
            $placeTag = $precinctTag->getParent();
            if ($placeTag) {
                if ($placeTag->getTitle()) {
                    if ($fullTitle) {
                        $fullTitle .= ', '.$placeTag->getTitle();
                    } else {
                        $fullTitle = $placeTag->getTitle();
                    }
                }
            }
        } elseif ($precinctEmbeddedTag->getTitle()) {
            $fullTitle = $precinctEmbeddedTag->getTitle();
        }

        return $fullTitle;
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
     * Get duration as not internationalized string. The format is type 78'12''.
     */
    public function getDurationString(int $duration): string
    {
        if (!$duration) {
            return '';
        }

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
        }

        return "0''";
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
     * @return \Doctrine\Common\Collections\Collection
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
     * @return string
     */
    public function getIframeUrl($multimediaObject, $isHTML5 = false, $isDownloadable = false)
    {
        $url = str_replace('%id%', $multimediaObject->isMultistream(), $multimediaObject->getProperty('opencasturl'));

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

    /**
     * @param null $tagCod
     * @param null $useBlockedTagAsGeneral
     *
     * @return string
     */
    public function getPathToTag($tagCod = null, $useBlockedTagAsGeneral = null)
    {
        return $this->linkService->generatePathToTag($tagCod, $useBlockedTagAsGeneral);
    }

    /**
     * Get next event session without sessions that reproducing now.
     *
     * @param array $event
     *
     * @throws \Exception
     *
     * @return \DateTime|string
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
     * @param MultimediaObject $multimediaObject
     *
     * @throws \Exception
     *
     * @return object
     */
    public function getLiveEventSession(MultimediaObject $multimediaObject)
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
     * @throws \MongoException
     *
     * @return int
     */
    public function getMMobjsFromSerie(Series $series)
    {
        $criteria = [
            'series' => new ObjectId($series),
            'status' => MultimediaObject::STATUS_PUBLISHED,
            'tags.cod' => 'PUCHWEBTV',
            'type' => ['$ne' => MultimediaObject::TYPE_LIVE],
        ];

        $multimediaObjects = $this->dm->getRepository(MultimediaObject::class)->findBy($criteria);

        return count($multimediaObjects);
    }
}
