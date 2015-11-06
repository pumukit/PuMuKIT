<?php

namespace Pumukit\WebTVBundle\Controller;

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
use Pumukit\WebTVBundle\Controller\PlayerController;


class MultimediaObjectController extends PlayerController
{
    /**
    * @Route("/video/{id}", name="pumukit_webtv_multimediaobject_index", defaults={"show_hide": true})
    * @Template("PumukitWebTVBundle:MultimediaObject:index.html.twig")
    */
    public function indexAction(MultimediaObject $multimediaObject, Request $request)
    {
        $response = $this->testBroadcast($multimediaObject, $request);
        if ($response instanceof Response) {
            return $response;
        }
        $response = $this->preExecute($multimediaObject, $request);
        if ($response instanceof Response) {
            return $response;
        }

        $track = $request->query->has('track_id') ?
        $multimediaObject->getTrackById($request->query->get('track_id')) :
        $multimediaObject->getFilteredTrackWithTags(array('display'));

        if (!$track) {
            throw $this->createNotFoundException();
        }

        $this->dispatchViewEvent($multimediaObject, $track);

        if ($track->containsTag('download')) {
            return $this->redirect($track->getUrl());
        }

        $this->updateBreadcrumbs($multimediaObject);

        return array('autostart' => $request->query->get('autostart', 'true'),
        'intro' => $this->getIntro($request->query->get('intro')),
        'multimediaObject' => $multimediaObject,
        'track' => $track, );
    }

    /**
    * @Route("/iframe/{id}", name="pumukit_webtv_multimediaobject_iframe", defaults={"show_hide": true})
    * @Template()
    */
    public function iframeAction(MultimediaObject $multimediaObject, Request $request)
    {
        $response = $this->testBroadcast($multimediaObject, $request);
        if ($response instanceof Response) {
            return $response;
        }

        $track = $request->query->has('track_id') ?
        $multimediaObject->getTrackById($request->query->get('track_id')) :
        $multimediaObject->getFilteredTrackWithTags(array('display'));

        if (!$track) {
            throw $this->createNotFoundException();
        }

        $this->dispatchViewEvent($multimediaObject, $track);

        if ($track->containsTag('download')) {
            return $this->redirect($track->getUrl());
        }

        return array('autostart' => $request->query->get('autostart', 'true'),
        'intro' => $this->getIntro($request->query->get('intro')),
        'multimediaObject' => $multimediaObject,
        'track' => $track, );
    }

    /**
    * @Route("/video/magic/{secret}", name="pumukit_webtv_multimediaobject_magicindex", defaults={"filter": false})
    * @Template("PumukitWebTVBundle:MultimediaObject:index.html.twig")
    */
    public function magicIndexAction(MultimediaObject $multimediaObject, Request $request)
    {
        $response = $this->testBroadcast($multimediaObject, $request);
        if ($response instanceof Response) {
            return $response;
        }
        $response = $this->preExecute($multimediaObject, $request);
        if ($response instanceof Response) {
            return $response;
        }

        $track = $request->query->has('track_id') ?
        $multimediaObject->getTrackById($request->query->get('track_id')) :
        $multimediaObject->getTrackWithTag('display');

        $this->dispatchViewEvent($multimediaObject, $track);

        if ($track->containsTag('download')) {
            return $this->redirect($track->getUrl());
        }

        $this->updateBreadcrumbs($multimediaObject);

        return array('autostart' => $request->query->get('autostart', 'true'),
        'intro' => $this->getIntro($request->query->get('intro')),
        'multimediaObject' => $multimediaObject,
        'track' => $track, );
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

    public function preExecute(MultimediaObject $multimediaObject, Request $request)
    {
        if ($opencasturl = $multimediaObject->getProperty('opencasturl')) {
            return $this->forward('PumukitWebTVBundle:Opencast:index', array('request' => $request, 'multimediaObject' => $multimediaObject));
        }
    }
}
