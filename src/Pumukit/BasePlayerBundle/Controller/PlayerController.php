<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Controller;

use Pumukit\BasePlayerBundle\Services\IntroService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\EmbeddedBroadcastService;
use Pumukit\SchemaBundle\Services\MultimediaObjectService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class PlayerController extends BasePlayerController
{
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        EmbeddedBroadcastService $embeddedBroadcastService,
        MultimediaObjectService $multimediaObjectService,
        IntroService $basePlayerIntroService
    ) {
        parent::__construct($eventDispatcher, $embeddedBroadcastService, $multimediaObjectService, $basePlayerIntroService);
        $this->eventDispatcher = $eventDispatcher;
        $this->embeddedBroadcastService = $embeddedBroadcastService;
        $this->multimediaObjectService = $multimediaObjectService;
        $this->basePlayerIntroService = $basePlayerIntroService;
    }

    /**
     * @Route("/player/{id}", name="pumukit_player_index")
     * @Route("/iframe/player/{id}", name="pumukit_player_index_iframe")
     *
     * @Template("@PumukitBasePlayer/player.html.twig")
     */
    public function indexAction(Request $request, MultimediaObject $multimediaObject)
    {
        return $this->doRender($request, $multimediaObject);
    }

    /**
     * @Route("/player/magic/{secret}", name="pumukit_player_magic_index")
     * @Route("/iframe/player/magic/{secret}", name="pumukit_player_magic_index_iframe")
     *
     * @Template("@PumukitBasePlayer/player.html.twig")
     */
    public function magicAction(Request $request, MultimediaObject $multimediaObject)
    {
        return $this->doRender($request, $multimediaObject);
    }

    private function doRender(Request $request, MultimediaObject $multimediaObject)
    {
        if ($response = $this->validateAccess($request, $multimediaObject)) {
            return $response;
        }

        return [
            'object' => $multimediaObject,
            'media' => $this->checkMultimediaObjectTracks($request, $multimediaObject),
        ];
    }
}
