<?php

namespace Pumukit\BasePlayerBundle\Twig;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\BasePlayerBundle\Services\TrackUrlService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

class BasePlayerExtension extends \Twig_Extension
{
    /**
     * @var RequestContext
     */
    protected $context;
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var TrackUrlService
     */
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
     * @param Track $track
     * @param int   $reference_type
     *
     * @return string
     */
    public function generateTrackFileUrl(Track $track, $reference_type = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->trackService->generateTrackFileUrl($track, $reference_type);
    }

    /**
     * @param Track   $track
     * @param Request $request
     *
     * @throws \Exception
     *
     * @return string
     */
    public function generateDirectTrackFileUrl(Track $track, Request $request)
    {
        return $this->trackService->generateDirectTrackFileUrl($track, $request);
    }

    /**
     * @param MultimediaObject $multimediaObject
     *
     * @return null|\Pumukit\SchemaBundle\Document\Track
     */
    public function getFirstPublicTrackFilter(MultimediaObject $multimediaObject)
    {
        return $multimediaObject->getDisplayTrack();
    }
}
