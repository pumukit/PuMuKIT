<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Broadcast;

class MultimediaObjectController extends Controller
{
    /**
     * @Route("/video/{id}")
     * @Template("PumukitWebTVBundle:MultimediaObject:index.html.twig")
     */
    public function indexByIdAction(MultimediaObject $multimediaObject, Request $request)
    {
      if (MultimediaObject::STATUS_PUBLISHED !== $multimediaObject->getStatus())
        throw $this->createNotFoundException();
     
      $track = $request->query->has('track_id') ?
        $multimediaObject->getTrackById($request->query->get('track_id')) :
        $multimediaObject->getTrackWithTag('display');

      if (!$track)
        throw $this->createNotFoundException();

      if (($broadcast = $multimediaObject->getBroadcast()) && 
          (Broadcast::BROADCAST_TYPE_PUB !== $broadcast->getBroadcastTypeId()))
        //TODO.
        throw $this->createNotFoundException();
        
      $serie = $multimediaObject->getSeries();
      $multimediaObjects = $serie->getMultimediaObjects();
      return array('multimediaObject' => $multimediaObject, 
                   'track' => $track,
                   'multimediaObjects' => $multimediaObjects);
     
    }


    /**
     * @Route("/video/magic/{secret}")
     * @Template("PumukitWebTVBundle:MultimediaObject:index.html.twig")
     */
    public function indexByMagicIdAction(MultimediaObject $multimediaObject, Request $request)
    {
      $track = $request->query->has('track_id') ?
        $multimediaObject->getTrackById($request->query->get('track_id')) :
        $multimediaObject->getTrackWithTag('display');

      $serie = $multimediaObject->getSeries();
      $multimediaObjects = $serie->getMultimediaObjects();
      return array('multimediaObject' => $multimediaObject, 
                   'track' => $track,
                   'multimediaObjects' => $multimediaObjects);
    }
}