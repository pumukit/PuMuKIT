<?php

namespace Pumukit\JWPlayerBundle\Controller;

use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\Series;
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
        $dm = $this->get('doctrine_mongodb')->getManager();
        if(!$series->isPlaylist()) {
            $criteria = array('islive' => false, 'embeddedBroadcast.type' => EmbeddedBroadcast::TYPE_PUBLIC);
            $mmobjs = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findBy($criteria,array('rank' => 'asc'));
        } else {
            $mmobjs = $playlistService->getPlaylistMmobjs($series);
        }

        return array(
            'playlist_mmobjs' => $mmobjs,
            'object' => $series,
            'responsive' => true,
        );
    }
}
