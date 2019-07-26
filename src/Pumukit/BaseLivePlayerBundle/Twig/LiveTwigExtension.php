<?php

namespace Pumukit\BaseLivePlayerBundle\Twig;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\BaseLivePlayerBundle\Services\LiveService;
use Pumukit\SchemaBundle\Document\EmbeddedEvent;
use Pumukit\SchemaBundle\Document\Event;
use Pumukit\SchemaBundle\Document\Live;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\EmbeddedEventSessionService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class LiveTwigExtension extends AbstractExtension
{
    /**
     * @var DocumentManager
     */
    private $dm;
    // @var LiveService
    private $liveService;
    /**
     * @var EmbeddedEventSessionService
     */
    private $eventsService;

    private $eventDefaultPic;

    public function __construct(DocumentManager $documentManager, LiveService $liveService, EmbeddedEventSessionService $eventsService, $eventDefaultPic)
    {
        $this->dm = $documentManager;
        $this->liveService = $liveService;
        $this->eventsService = $eventsService;
        $this->eventDefaultPic = $eventDefaultPic;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('generate_hls_url', [$this, 'genHlsUrl']),
            new TwigFunction('future_and_not_finished_event', [$this, 'getFutureAndNotFinishedEvent']),
            new TwigFunction('poster_pic', [$this, 'getPosterPic']),
            new TwigFunction('poster_pic_text_color', [$this, 'getPosterPicTextColor']),
            new TwigFunction('poster', [$this, 'getEventPoster']),
            new TwigFunction('poster_text_color', [$this, 'getPosterTextColor']),
            new TwigFunction('event_first_thumbnail', [$this, 'getEventThumbnail']),
            new TwigFunction('event_default_pic', [$this, 'getEventDefaultPic']),
        ];
    }

    /**
     * Generate HLS URL from RTMP url.
     *
     * Original twig template:
     *    {{ live.url|replace({'rtmp://':'http://', 'rtmpt://': 'http://'}) }}/{{ live.sourcename }}/playlist.m3u8
     *
     * @param Live $live
     *
     * @return string
     */
    public function genHlsUrl(Live $live)
    {
        return $this->liveService->generateHlsUrl($live);
    }

    /**
     * Get future and not finished event.
     *
     * @param null      $limit
     * @param null|Live $live
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function getFutureAndNotFinishedEvent($limit = null, Live $live = null)
    {
        $eventRepo = $this->dm->getRepository(Event::class);

        return $eventRepo->findFutureAndNotFinished($limit, null, $live);
    }

    /**
     * @param EmbeddedEvent $event
     *
     * @deprecated use getPosterPic
     *
     * @return string
     */
    public function getEventPoster(EmbeddedEvent $event)
    {
        return $this->eventsService->getEventPoster($event);
    }

    /**
     * Get event poster.
     *
     * @param MultimediaObject $multimediaObject
     *
     * @return string
     */
    public function getPosterPic(MultimediaObject $multimediaObject)
    {
        return $this->eventsService->getEventPicPoster($multimediaObject);
    }

    /**
     * @param EmbeddedEvent $event
     *
     * @deprecated use getPosterPicTextColor
     *
     * @return string
     */
    public function getPosterTextColor(EmbeddedEvent $event)
    {
        return $this->eventsService->getPosterTextColor($event);
    }

    /**
     * Get poster text color.
     *
     * @param MultimediaObject $multimediaObject
     *
     * @return string
     */
    public function getPosterPicTextColor(MultimediaObject $multimediaObject)
    {
        return $this->eventsService->getPicPosterTextColor($multimediaObject);
    }

    /**
     * Get event thumbnail.
     *
     * @param array|EmbeddedEvent $event
     *
     * @return string
     */
    public function getEventThumbnail($event)
    {
        if (!is_array($event)) {
            return $this->eventsService->getEventThumbnail($event);
        }

        return $this->eventsService->getEventThumbnailByEventId($event['event']['_id']);
    }

    /**
     * @return mixed
     */
    public function getEventDefaultPic()
    {
        return $this->eventDefaultPic;
    }
}
