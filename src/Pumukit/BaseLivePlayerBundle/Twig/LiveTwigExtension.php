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
    private $dm;
    private $liveService;
    private $eventsService;
    private $eventDefaultPic;

    public function __construct(DocumentManager $documentManager, LiveService $liveService, EmbeddedEventSessionService $eventsService, string $eventDefaultPic)
    {
        $this->dm = $documentManager;
        $this->liveService = $liveService;
        $this->eventsService = $eventsService;
        $this->eventDefaultPic = $eventDefaultPic;
    }

    public function getFunctions(): array
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
     */
    public function genHlsUrl(Live $live): string
    {
        return $this->liveService->generateHlsUrl($live);
    }

    /**
     * @throws \Exception
     */
    public function getFutureAndNotFinishedEvent(int $limit = null, ?Live $live = null)
    {
        $eventRepo = $this->dm->getRepository(Event::class);

        return $eventRepo->findFutureAndNotFinished($limit, null, $live);
    }

    /**
     * @deprecated use getPosterPic
     */
    public function getEventPoster(EmbeddedEvent $event): string
    {
        return $this->eventsService->getEventPoster($event);
    }

    public function getPosterPic(MultimediaObject $multimediaObject): string
    {
        return $this->eventsService->getEventPicPoster($multimediaObject);
    }

    /**
     * @deprecated use getPosterPicTextColor
     */
    public function getPosterTextColor(EmbeddedEvent $event): string
    {
        return $this->eventsService->getPosterTextColor($event);
    }

    /**
     * @param MultimediaObject $multimediaObject
     */
    public function getPosterPicTextColor(MultimediaObject $multimediaObject): string
    {
        return $this->eventsService->getPicPosterTextColor($multimediaObject);
    }

    public function getEventThumbnail($event): string
    {
        if (!is_array($event)) {
            return $this->eventsService->getEventThumbnail($event);
        }

        return $this->eventsService->getEventThumbnailByEventId($event['event']['_id']);
    }

    public function getEventDefaultPic(): string
    {
        return $this->eventDefaultPic;
    }
}
