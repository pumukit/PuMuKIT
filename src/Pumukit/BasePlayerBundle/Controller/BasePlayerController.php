<?php

namespace Pumukit\BasePlayerBundle\Controller;

use Pumukit\BasePlayerBundle\Event\BasePlayerEvents;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;
use Pumukit\BasePlayerBundle\Services\IntroService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Services\EmbeddedBroadcastService;
use Pumukit\SchemaBundle\Services\MultimediaObjectService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

abstract class BasePlayerController extends Controller
{
    /**
     * @Route("/videoplayer/{id}", name="pumukit_videoplayer_index" )
     */
    abstract public function indexAction(Request $request, EmbeddedBroadcastService $embeddedBroadcastService, MultimediaObjectService $multimediaObjectService, IntroService $basePlayerIntroService, MultimediaObject $multimediaObject);

    /**
     * @Route("/videoplayer/magic/{secret}", name="pumukit_videoplayer_magicindex")
     */
    abstract public function magicAction(Request $request, EmbeddedBroadcastService $embeddedBroadcastService, MultimediaObjectService $multimediaObjectService, IntroService $basePlayerIntroService, MultimediaObject $multimediaObject);

    protected function dispatchViewEvent(MultimediaObject $multimediaObject, Track $track = null): void
    {
        $event = new ViewedEvent($multimediaObject, $track);
        $this->get('event_dispatcher')->dispatch(BasePlayerEvents::MULTIMEDIAOBJECT_VIEW, $event);
    }
}
