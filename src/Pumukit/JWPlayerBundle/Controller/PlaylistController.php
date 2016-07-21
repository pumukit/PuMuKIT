<?php

namespace Pumukit\JWPlayerBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Pumukit\BasePlayerBundle\Controller\BasePlaylistController;

class PlaylistController extends BasePlaylistController
{
    /**
     * @Route("/playlist/{id}", name="pumukit_playlistplayer_index", defaults={"no_channels": true} )
     * @Route("/playlist/magic/{secret}", name="pumukit_playlistplayer_magicindex", defaults={"show_hide": true, "no_channels": true} )
     * @Template("PumukitJWPlayerBundle:JWPlayer:player_playlist.html.twig")
     */
    public function indexAction(Series $series, Request $request)
    {
        $playlistService = $this->get('pumukit_baseplayer.seriesplaylist');
        $mmobjs = $playlistService->getPlaylistMmobjs($series);
        return array(
            'playlist_mmobjs' => $mmobjs,
            'object' => $series,
            'responsive' => true,
        );
    }
}
