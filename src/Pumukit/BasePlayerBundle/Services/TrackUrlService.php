<?php

namespace Pumukit\BasePlayerBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Track;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TrackUrlService
{
    private $dm;
    private $router;

    public function __construct(DocumentManager $dm, UrlGeneratorInterface $router)
    {
        $this->dm = $dm;
        $this->router = $router;
    }

    public function generateTrackFileUrl(Track $track, $reference_type = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        $ext = pathinfo($track->getUrl(), PATHINFO_EXTENSION);
        $params = array(
            'id' => $track->getId(),
            'ext' => $ext,
        );
        $url = $this->router->generate('pumukit_trackfile_index', $params, $reference_type);
        return $url;
    }
}
