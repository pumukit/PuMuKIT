<?php

namespace Pumukit\BasePlayerBundle\Controller;

use Pumukit\BasePlayerBundle\Event\BasePlayerEvents;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

abstract class BasePlayerController extends Controller
{
    /**
     * @Route("/videoplayer/{id}", name="pumukit_videoplayer_index" )
     */
    abstract public function indexAction(MultimediaObject $multimediaObject, Request $request);

    /**
     * @Route("/videoplayer/magic/{secret}", name="pumukit_videoplayer_magicindex")
     */
    abstract public function magicAction(MultimediaObject $multimediaObject, Request $request);

    protected function dispatchViewEvent(MultimediaObject $multimediaObject, Track $track = null)
    {
        $event = new ViewedEvent($multimediaObject, $track);
        $this->get('event_dispatcher')->dispatch(BasePlayerEvents::MULTIMEDIAOBJECT_VIEW, $event);
    }

    /**
     * @deprecated Will be removed in version 2.5.x
     *             Use lines in this function instead
     */
    protected function testBroadcast(MultimediaObject $multimediaObject, Request $request)
    {
        $embeddedBroadcastService = $this->get('pumukitschema.embeddedbroadcast');
        $password = $request->get('broadcast_password');

        return $embeddedBroadcastService->canUserPlayMultimediaObject($multimediaObject, $this->getUser(), $password);
    }

    /**
     * @deprecated Will be removed in version 2.5.x
     *             Use lines in this function instead
     *
     * @param mixed $queryIntro
     */
    protected function getIntro($queryIntro = false)
    {
        return $this->get('pumukit_baseplayer.intro')->getIntro($queryIntro);
    }
}
