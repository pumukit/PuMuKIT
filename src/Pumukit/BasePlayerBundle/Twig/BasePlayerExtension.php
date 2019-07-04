<?php

namespace Pumukit\BasePlayerBundle\Twig;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\BasePlayerBundle\Services\TrackUrlService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

class BasePlayerExtension extends \Twig_Extension
{
    /**
     * @var RequestContext
     */
    protected $context;

    private $dm;
    private $trackService;

    public function __construct(DocumentManager $documentManager, RequestContext $context, TrackUrlService $trackService)
    {
        $this->dm = $documentManager;
        $this->context = $context;
        $this->trackService = $trackService;
    }

    /**
     * Get functions.
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('track_url', [$this, 'generateTrackFileUrl']),
            new \Twig_SimpleFunction('direct_track_url', [$this, 'generateDirectTrackFileUrl']),
        ];
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('first_public_track', [$this, 'getFirstPublicTrackFilter']),
        ];
    }

    /**
     * @param $track
     * @param int $reference_type
     *
     * @return string
     */
    public function generateTrackFileUrl($track, $reference_type = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->trackService->generateTrackFileUrl($track, $reference_type);
    }

    /**
     * @param $track
     * @param $request
     *
     * @throws \Exception
     *
     * @return string
     */
    public function generateDirectTrackFileUrl($track, $request)
    {
        return $this->trackService->generateDirectTrackFileUrl($track, $request);
    }

    /**
     * @param MultimediaObject $mmobj
     *
     * @return null|\Pumukit\SchemaBundle\Document\Track
     */
    public function getFirstPublicTrackFilter(MultimediaObject $mmobj)
    {
        return $mmobj->getDisplayTrack();
    }
}
