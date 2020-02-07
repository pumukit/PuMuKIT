<?php

namespace Pumukit\BasePlayerBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\BasePlayerBundle\Services\SeriesPlaylistService;
use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

abstract class BasePlaylistController extends AbstractController implements WebTVControllerInterface
{
    /**
     * @Route("/playlist/{id}", name="pumukit_playlistplayer_index", defaults={"no_channels": true} )
     * @Route("/playlist/magic/{secret}", name="pumukit_playlistplayer_magicindex", defaults={"show_hide": true, "no_channels": true} )
     */
    abstract public function indexAction(Request $request, DocumentManager $documentManager, SeriesPlaylistService $seriesPlaylistService, Series $series);
}
