<?php


/**
TODO:
 - Add doctrine filters.
 -      
 */

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\Broadcast;

class MultimediaObjectController extends Controller
{
    /**
     * @Route("/video/{id}")
     * @Template("PumukitWebTVBundle:MultimediaObject:index.html.twig")
     */
    public function indexAction(MultimediaObject $multimediaObject, Request $request)
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

      $this->incNumView($multimediaObject, $track);
        
      return array('autostart' => $request->query->get('autostart', 'true'),
                   'multimediaObject' => $multimediaObject, 
                   'track' => $track,
                   'intro' => $this->getIntro($request->query->get('intro')));
    }


   /**
     * @Route("/iframe/{id}")
     * @Template()
     */
    public function iframeAction(MultimediaObject $multimediaObject, Request $request)
    {
      return $this->indexAction($multimediaObject, $request);
    }


    /**
     * @Route("/video/magic/{secret}")
     * @Template("PumukitWebTVBundle:MultimediaObject:index.html.twig")
     */
    public function magicIndexAction(MultimediaObject $multimediaObject, Request $request)
    {
      //TODO
      $track = $request->query->has('track_id') ?
        $multimediaObject->getTrackById($request->query->get('track_id')) :
        $multimediaObject->getTrackWithTag('display');

      $this->incNumView($multimediaObject, $track);

      return array('multimediaObject' => $multimediaObject, 
                   'track' => $track,
                   'intro' => $this->getIntro($request->query->get('intro')));
    }


    /**
     * @Template()
     */
    public function seriesAction(MultimediaObject $multimediaObject)
    {
      $series = $multimediaObject->getSeries();
      $multimediaObjects = $series->getMultimediaObjects();

      return array('series' => $series,
                   'multimediaObjects' => $multimediaObjects);
    }

    /**
     * @Template()
     */
    public function relatedAction(MultimediaObject $multimediaObject)
    {
      $mmobjRepo = $this
        ->get('doctrine_mongodb.odm.document_manager')
        ->getRepository('PumukitSchemaBundle:MultimediaObject');
      $relatedMms = $mmobjRepo->findRelatedMultimediaObjects($multimediaObject);

      return array('multimediaObjects' => $relatedMms);
    }



    private function getIntro($queryIntro=false)
    {
      $hasIntro = $this->container->hasParameter('pumukit2.intro');
      
      if ($queryIntro && filter_var($queryIntro, FILTER_VALIDATE_URL)) {
        $intro = $queryIntro;
      } elseif($hasIntro) {
        $intro = $this->container->getParameter('pumukit2.intro');
      } else {
        $intro = false;
      }

      return $intro;
    }

    private function incNumView(MultimediaObject $multimediaObject, Track $track=null)
    {
      $dm = $this->get('doctrine_mongodb.odm.document_manager');
      $multimediaObject->incNumview();
      $track && $track->incNumview();
      $dm->persist($multimediaObject);
      $dm->flush();
    }
}