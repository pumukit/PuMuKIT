<?php

namespace Pumukit\BasePlayerBundle\Twig;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\BasePlayerBundle\Services\TrackUrlService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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

    public function getName()
    {
        return 'baseplayer_extension';
    }

    /**
     * Get functions.
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('track_url', array($this, 'generateTrackFileUrl')),
            new \Twig_SimpleFunction('direct_track_url', array($this, 'generateDirectTrackFileUrl')),
        );
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('first_public_track', array($this, 'getFirstPublicTrackFilter')),
        );
    }

    /**
     * @param Track $track    Track to get an url for
     * @param bool  $absolute return absolute path
     *
     * @return string
     */
    public function generateTrackFileUrl($track, $reference_type = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->trackService->generateTrackFileUrl($track, $reference_type);
    }

    /**
     * @param Track  $track    Track to get an url for
     * @param string $clientIp Client IP from request
     *
     * @return string
     */
    public function generateTrackFileUrl($track, $clientIp)
    {
        return $this->trackService->generateDirectTrackFileUrl($track, $clientIp);
    }

    /**
     * @param MultimediaObject $mmobj
     *
     * @return Track
     */
    public function getFirstPublicTrackFilter(MultimediaObject $mmobj)
    {
        return $mmobj->getDisplayTrack();
    }
}
