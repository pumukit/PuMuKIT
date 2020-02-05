<?php

namespace Pumukit\BasePlayerBundle\Controller;

use Pumukit\BasePlayerBundle\Event\BasePlayerEvents;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

abstract class BasePlayerController extends AbstractController
{
    /**
     * @Route("/videoplayer/{id}", name="pumukit_videoplayer_index" )
     */
    abstract public function indexAction(MultimediaObject $multimediaObject, Request $request);

    /**
     * @Route("/videoplayer/magic/{secret}", name="pumukit_videoplayer_magicindex")
     */
    abstract public function magicAction(MultimediaObject $multimediaObject, Request $request);

    protected function dispatchViewEvent(MultimediaObject $multimediaObject, Track $track = null, EventDispatcher $eventDispatcher): void
    {
        $event = new ViewedEvent($multimediaObject, $track);
        $eventDispatcher->dispatch(BasePlayerEvents::MULTIMEDIAOBJECT_VIEW, $event);
    }
}
