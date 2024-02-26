<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Twig;

use Pumukit\BasePlayerBundle\Services\TrackUrlService;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class BasePlayerExtension extends AbstractExtension
{
    protected $context;
    private $trackService;

    public function __construct(RequestContext $context, TrackUrlService $trackService)
    {
        $this->context = $context;
        $this->trackService = $trackService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('track_url', [$this, 'generateTrackFileUrl']),
            new TwigFunction('direct_track_url', [$this, 'generateDirectTrackFileUrl']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('first_public_track', [$this, 'getFirstPublicTrackFilter']),
        ];
    }

    public function generateTrackFileUrl(Track $track, int $reference_type = UrlGeneratorInterface::ABSOLUTE_PATH): ?string
    {
        return $this->trackService->generateTrackFileUrl($track, $reference_type);
    }

    public function generateDirectTrackFileUrl(Track $track, Request $request): string
    {
        return $this->trackService->generateDirectTrackFileUrl($track, $request);
    }

    public function getFirstPublicTrackFilter(MultimediaObject $multimediaObject): ?Track
    {
        return $multimediaObject->getDisplayTrack();
    }
}
