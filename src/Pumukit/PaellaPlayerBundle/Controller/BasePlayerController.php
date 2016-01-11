<?php

namespace Pumukit\PaellaPlayerBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Pumukit\SchemaBundle\Document\Track;

use Pumukit\BasePlayerBundle\Controller\BasePlayerController as BasePlayerControllero;

class BasePlayerController extends BasePlayerControllero
{
    /**
     * @Route("/videoplayer/{id}", name="pumukit_videoplayer_index" )
     * @Route("/videoplayer/opencast/{id}", name="pumukit_videoplayer_opencast" )
     * @Template("PumukitPaellaPlayerBundle:PaellaPlayer:index.html.twig")
     */
    public function indexAction(MultimediaObject $multimediaObject, Request $request)
    {
        $response = $this->testBroadcast($multimediaObject, $request);
        if ($response instanceof Response) {
            return $response;
        }

        $track = $request->query->has('track_id') ?
        $multimediaObject->getTrackById($request->query->get('track_id')) :
        $multimediaObject->getFilteredTrackWithTags(array('display'));

        $this->dispatchViewEvent($multimediaObject, $track);

        if($track && $track->containsTag("download")) {       
            return $this->redirect($track->getUrl());
        }
        //ADD LOGIC TO CHECK IF VIDEO IS MULTISTREAM (opencast)
        //Then just return several tracks.
        $tracks = array($track);

        return array('autostart' => $request->query->get('autostart', 'true'),
                     'intro' => $this->getIntro($request->query->get('intro')),
                     'multimediaObject' => $multimediaObject,
                     'tracks' => $tracks);
    }
}
