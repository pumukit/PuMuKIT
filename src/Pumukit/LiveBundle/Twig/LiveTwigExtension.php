<?php

namespace Pumukit\LiveBundle\Twig;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\LiveBundle\Document\Live;
use Pumukit\LiveBundle\Services\LiveService;

class LiveTwigExtension extends \Twig_Extension
{
    private $dm;
    private $liveService;

    public function __construct(DocumentManager $documentManager, LiveService $liveService)
    {
        $this->dm = $documentManager;
        $this->liveService = $liveService;
    }

    public function getName()
    {
        return 'pumukit_live_twig_extension';
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('generate_hls_url', array($this, 'genHlsUrl')),
            new \Twig_SimpleFunction('future_and_not_finished_event', array($this, 'getFutureAndNotFinishedEvent')),
        );
    }

    /**
     * Generate HLS URL from RTMP url.
     *
     * Original twig template:
     *    {{ live.url|replace({'rtmp://':'http://', 'rtmpt://': 'http://'}) }}/{{ live.sourcename }}/playlist.m3u8
     *
     * @param Live $live
     */
    public function genHlsUrl(Live $live)
    {
        return $this->liveService->generateHlsUrl($live);
    }

    /**
     * Get future and not finished event.
     *
     * @param int $limit
     *
     * @return Event $event
     */
    public function getFutureAndNotFinishedEvent($limit = null, Live $live = null)
    {
        $eventRepo = $this->dm->getRepository('PumukitLiveBundle:Event');

        return $eventRepo->findFutureAndNotFinished($limit, null, $live);
    }
}
