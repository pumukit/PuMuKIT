<?php

declare(strict_types=1);

namespace Pumukit\JWPlayerBundle\Controller;

use Pumukit\BasePlayerBundle\Controller\BasePlaylistController;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PlaylistController extends BasePlaylistController
{
    /**
     * @Route("/playlist/{id}", name="pumukit_playlistplayer_index", defaults={"no_channels"=true} )
     * @Route("/playlist/magic/{secret}", name="pumukit_playlistplayer_magicindex", defaults={"show_hide"=true, "no_channels"=true} )
     * @Template("@PumukitJWPlayer/JWPlayer/player_playlist.html.twig")
     */
    public function indexAction(Request $request, Series $series)
    {
        if (!$series->isPlaylist()) {
            $criteria = [
                'type' => ['$ne' => MultimediaObject::TYPE_LIVE],
                'embeddedBroadcast.type' => EmbeddedBroadcast::TYPE_PUBLIC,
                'tracks' => ['$elemMatch' => ['tags' => 'display', 'hide' => false]],
            ];
            $mmobjs = $this->documentManager->getRepository(MultimediaObject::class)->findBy($criteria, ['rank' => 'asc']);
        } else {
            $mmobjs = $this->seriesPlaylistService->getPlaylistMmobjs($series);
        }

        return [
            'playlist_mmobjs' => $mmobjs,
            'object' => $series,
            'responsive' => true,
        ];
    }
}
