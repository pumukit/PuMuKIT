<?php

declare(strict_types=1);

namespace Pumukit\PaellaPlayerBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\BasePlayerBundle\Controller\BasePlaylistController as BasePlaylistAbstractController;
use Pumukit\BasePlayerBundle\Services\SeriesPlaylistService;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BasePlaylistController extends BasePlaylistAbstractController
{
    private $pumukitOpencastHost;
    private $paellaCustomCssUrl;
    private $paellaLogo;
    private $pumukitIntro;
    private $paellaAutoPlay;

    public function __construct(
        DocumentManager $documentManager,
        SeriesPlaylistService $seriesPlaylistService,
        $pumukitOpencastHost,
        $paellaCustomCssUrl,
        $paellaLogo,
        $pumukitIntro,
        $paellaAutoPlay
    ) {
        parent::__construct($documentManager, $seriesPlaylistService);
        $this->pumukitOpencastHost = $pumukitOpencastHost;
        $this->paellaCustomCssUrl = $paellaCustomCssUrl;
        $this->paellaLogo = $paellaLogo;
        $this->pumukitIntro = $pumukitIntro;
        $this->paellaAutoPlay = $paellaAutoPlay;
    }

    /**
     * @Route("/playlist/{id}", name="pumukit_playlistplayer_index", defaults={"no_channels": true} )
     * @Route("/playlist/magic/{secret}", name="pumukit_playlistplayer_magicindex", defaults={"show_hide": true, "no_channels": true} )
     */
    public function indexAction(Request $request, Series $series)
    {
        return $this->redirectWithMmobj($series, $request, $request->get('videoId'), $request->get('videoPos'));
    }

    /**
     * @Route("/playlist", name="pumukit_playlistplayer_paellaindex", defaults={"no_channels": true} )
     */
    #[Template('@PumukitPaellaPlayer/PaellaPlayer/player.html.twig')]
    public function paellaIndexAction(Request $request)
    {
        $mmobjId = $request->get('videoId');
        $seriesId = $request->get('playlistId');
        $series = $this->documentManager->getRepository(Series::class)->find($seriesId);
        if (!$series) {
            return $this->return404Response("No playlist found with id: {$seriesId}");
        }

        if (!$mmobjId) {
            // If the player has no mmobjId, we should provide it ourselves.
            return $this->redirectWithMmobj($series, $request);
        }

        $criteria = ['embeddedBroadcast.type' => ['$eq' => EmbeddedBroadcast::TYPE_PUBLIC]];
        if (!$series->isPlaylist()) {
            $criteria = [
                'series' => new ObjectId($series->getId()),
                'embeddedBroadcast.type' => EmbeddedBroadcast::TYPE_PUBLIC,
                'type' => ['$ne' => MultimediaObject::TYPE_LIVE],
                'tracks.tags' => 'display',
            ];
            $mmobj = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy($criteria, ['rank' => 'asc']);
        } else {
            $mmobj = $this->seriesPlaylistService->getMmobjFromIdAndPlaylist($mmobjId, $series, $criteria);
        }

        if (!$mmobj) {
            return $this->return404Response("No playable multimedia object found with id: {$mmobjId} belonging to this playlist. ({$series->getTitle()})");
        }

        $opencastHost = $this->pumukitOpencastHost ?? '';

        return [
            'autostart' => $request->query->get('autostart', 'false'),
            'autoplay_fallback' => $this->paellaAutoPlay,
            'intro' => $this->getIntro($request->query->get('intro')),
            'tail' => null,
            'custom_css_url' => $this->paellaCustomCssUrl,
            'logo' => $this->paellaLogo,
            'multimediaObject' => $mmobj,
            'object' => $series,
            'responsive' => true,
            'opencast_host' => $opencastHost,
        ];
    }

    protected function getIntro($introParameter = null): bool
    {
        $hasIntro = $this->pumukitIntro;

        $showIntro = true;
        if (null !== $introParameter && false === filter_var($introParameter, FILTER_VALIDATE_BOOLEAN)) {
            $showIntro = false;
        }

        if ($hasIntro && $showIntro) {
            return $this->pumukitIntro;
        }

        return false;
    }

    private function redirectWithMmobj(Series $series, Request $request, $mmobjId = null, $videoPos = null)
    {
        if (!$mmobjId) {
            $criteria = ['embeddedBroadcast.type' => ['$eq' => EmbeddedBroadcast::TYPE_PUBLIC]];
            if (!$series->isPlaylist()) {
                $criteria = [
                    'series' => new ObjectId($series->getId()),
                    'embeddedBroadcast.type' => EmbeddedBroadcast::TYPE_PUBLIC,
                    'type' => ['$ne' => MultimediaObject::TYPE_LIVE],
                    'tracks.tags' => 'display',
                ];
                $mmobj = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy($criteria, ['rank' => 'asc']);
            } else {
                $mmobj = $this->seriesPlaylistService->getPlaylistFirstMmobj($series, $criteria);
            }

            if (!$mmobj) {
                return $this->return404Response('This playlist does not have any playable multimedia objects.');
            }
            $mmobjId = $mmobj->getId();
            $videoPos = 0;
        }
        $redirectUrl = $this->generateUrl(
            'pumukit_playlistplayer_paellaindex',
            [
                'playlistId' => $series->getId(),
                'videoId' => $mmobjId,
                'videoPos' => $videoPos,
                'autostart' => $request->query->get('autostart', 'false'),
            ]
        );

        return $this->redirect($redirectUrl);
    }

    private function return404Response($message = ''): Response
    {
        $params = [
            'message' => $message,
        ];
        $template = $this->renderView(
            '@PumukitPaellaPlayer/PaellaPlayer/404exception.html.twig',
            $params
        );

        return new Response($template, 404);
    }
}
