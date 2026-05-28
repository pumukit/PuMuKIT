<?php

declare(strict_types=1);

namespace Pumukit\PaellaPlayerBundle\Controller;

use Pumukit\BasePlayerBundle\Controller\BasePlayerController as BasePlayerAbstractController;
use Pumukit\BasePlayerBundle\Services\IntroService;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\EmbeddedBroadcastService;
use Pumukit\SchemaBundle\Services\MultimediaObjectService;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BasePlayerController extends BasePlayerAbstractController
{
    private $pumukitOpencastHost;
    private $paellaCustomCssUrl;
    private $paellaLogo;
    private $pumukitIntro;
    private $paellaAutoPlay;
    private $pumukitPlayerWhenDispatchViewEvent;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        EmbeddedBroadcastService $embeddedBroadcastService,
        MultimediaObjectService $multimediaObjectService,
        IntroService $basePlayerIntroService,
        $pumukitOpencastHost,
        $paellaCustomCssUrl,
        $paellaLogo,
        $pumukitIntro,
        $paellaAutoPlay,
        string $pumukitPlayerWhenDispatchViewEvent
    ) {
        parent::__construct($eventDispatcher, $embeddedBroadcastService, $multimediaObjectService, $basePlayerIntroService);
        $this->pumukitOpencastHost = $pumukitOpencastHost;
        $this->paellaCustomCssUrl = $paellaCustomCssUrl;
        $this->paellaLogo = $paellaLogo;
        $this->pumukitIntro = $pumukitIntro;
        $this->paellaAutoPlay = $paellaAutoPlay;
        $this->pumukitPlayerWhenDispatchViewEvent = $pumukitPlayerWhenDispatchViewEvent;
    }

    /**
     * @Route("/videoplayer/{id}", name="pumukit_videoplayer_index")
     * @Route("/videoplayer/opencast/{id}", name="pumukit_videoplayer_opencast")
     */
    #[Template('@PumukitPaellaPlayer/PaellaPlayer/player.html.twig')]
    public function indexAction(Request $request, MultimediaObject $multimediaObject)
    {
        return $this->doRender($request, $multimediaObject);
    }

    /**
     * @Route("/videoplayer/magic/{secret}", name="pumukit_videoplayer_magicindex")
     */
    #[Template('@PumukitPaellaPlayer/PaellaPlayer/player.html.twig')]
    public function magicAction(Request $request, MultimediaObject $multimediaObject)
    {
        $secret = $request->attributes->get('secret') ?? $request->query->get('secret');
        if (!$secret) {
            $params = $request->query->all();
            $params['id'] = $multimediaObject->getId();
            $params['secret'] = $multimediaObject->getSecret();

            return $this->redirect(
                $this->generateUrl('pumukit_videoplayer_magicindex', $params)
            );
        }

        return $this->doRender($request, $multimediaObject);
    }

    private function doRender(Request $request, MultimediaObject $multimediaObject)
    {
        if ($response = $this->validateAccess($request, $multimediaObject)) {
            return $response;
        }

        $track = $this->checkMultimediaObjectTracks($request, $multimediaObject);
        if ($track instanceof RedirectResponse) {
            return $track;
        }

        $tracks = $this->getMultimediaObjectMultiStreamTracks($multimediaObject, $track);

        return $this->getParametersForPlayer($request, $multimediaObject, $tracks);
    }

    private function getAutoStart(Request $request)
    {
        if ('disabled' === $this->paellaAutoPlay) {
            return false;
        }

        $autoStart = $request->query->get('autostart', 'false');
        $userAgent = $request->headers->get('user-agent');
        if ((false !== strpos($userAgent, 'Safari')) && false === strpos($userAgent, 'Chrome')) {
            $autoStart = false;
        }

        return $autoStart;
    }

    private function generateBasePlayerRaw(Request $request, MultimediaObject $multimediaObject, Track $track): Response
    {
        return $this->render('@PumukitPaellaPlayer/BasePlayer/player.html.twig', [
            'autostart' => $this->getAutoStart($request),
            'autoplay_fallback' => $this->paellaAutoPlay,
            'when_dispatch_view_event' => $this->pumukitPlayerWhenDispatchViewEvent,
            'multimediaObject' => $multimediaObject,
            'track' => $track,
        ]);
    }

    private function getParametersForPlayer(Request $request, MultimediaObject $multimediaObject, array $tracks): array
    {
        return [
            'autostart' => $this->getAutoStart($request),
            'autoplay_fallback' => $this->paellaAutoPlay,
            'intro' => $this->basePlayerIntroService->getVideoIntroduction($multimediaObject, $request->query->getBoolean('intro')),
            'tail' => $this->basePlayerIntroService->getVideoTail($multimediaObject),
            'custom_css_url' => $this->paellaCustomCssUrl,
            'logo' => $this->paellaLogo,
            'multimediaObject' => $multimediaObject,
            'object' => $multimediaObject,
            'when_dispatch_view_event' => $this->pumukitPlayerWhenDispatchViewEvent,
            'tracks' => $tracks,
            'opencast_host' => $this->pumukitOpencastHost,
        ];
    }
}
