<?php

namespace Pumukit\Legacy\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;
use Pumukit\BasePlayerBundle\Event\BasePlayerEvents;

class MultimediaObjectController extends Controller
{
    /**
     * @Route("/video/{id}", name="pumukit_webtv_multimediaobject_index" )
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

        $this->dispatch($multimediaObject, $track);

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
     * @Route("/iframe/{id}", name="pumukit_webtv_multimediaobject_iframe" )
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

        $this->incNumView($multimediaObject, $track);
        $this->dispatch($multimediaObject, $track);

        if ($track && $track->containsTag('download')) {
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
        $mmobjService = $this->get('pumukitschema.multimedia_object');
        if ($mmobjService->isPublished($multimediaObject, 'PUCHWEBTV')) {
            if ($mmobjService->hasPlayableResource($multimediaObject) && Broadcast::BROADCAST_TYPE_PUB === $multimediaObject->getBroadcast()->getBroadcastTypeId()) {
                return $this->redirect($this->generateUrl('pumukit_webtv_multimediaobject_index', array('id' => $multimediaObject->getId())));
            }
        } elseif (($multimediaObject->getStatus() != MultimediaObject::STATUS_PUBLISHED
                 && $multimediaObject->getStatus() != MultimediaObject::STATUS_HIDE
                 ) || !$multimediaObject->containsTagWithCod('PUCHWEBTV')) {
            return $this->render('PumukitWebTVBundle:Index:404notfound.html.twig');
        }

        $response = $this->preExecute($multimediaObject, $request);
        if ($response instanceof Response) {
            return $response;
        }

        $track = $request->query->has('track_id') ?
                 $multimediaObject->getTrackById($request->query->get('track_id')) :
                 $multimediaObject->getTrackWithTag('display');

        if ($track) {
            $this->incNumView($multimediaObject, $track);
            $this->dispatch($multimediaObject, $track);
            if ($track->containsTag('download')) {
                return $this->redirect($track->getUrl());
            }
        }

        $this->updateBreadcrumbs($multimediaObject);

        return array('autostart' => $request->query->get('autostart', 'true'),
                     'intro' => $this->getIntro($request->query->get('intro')),
                     'multimediaObject' => $multimediaObject,
                     'track' => $track,
                     'magic_url' => true, );
    }

    /**
     * @Template()
     */
    public function seriesAction(MultimediaObject $multimediaObject)
    {
        $series = $multimediaObject->getSeries();
        $multimediaObjects = $series->getMultimediaObjects();

        $tagRepo = $this
        ->get('doctrine_mongodb.odm.document_manager')
        ->getRepository('PumukitSchemaBundle:Tag');
        $unescoTag = $tagRepo->findOneByCod('UNESCO');

        return array('series' => $series,
                   'multimediaObjects' => $multimediaObjects,
                   'unescoTag' => $unescoTag, );
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

        $tagRepo = $this
        ->get('doctrine_mongodb.odm.document_manager')
        ->getRepository('PumukitSchemaBundle:Tag');
        $unescoTag = $tagRepo->findOneByCod('UNESCO');

        return array('multimediaObjects' => $relatedMms,
                   'unescoTag' => $unescoTag, );
    }

    protected function getIntro($queryIntro = false)
    {
        $hasIntro = $this->container->hasParameter('pumukit2.intro');

        if ($queryIntro && filter_var($queryIntro, FILTER_VALIDATE_URL)) {
            $intro = $queryIntro;
        } elseif ($hasIntro) {
            $intro = $this->container->getParameter('pumukit2.intro');
        } else {
            $intro = false;
        }

        return $intro;
    }

    protected function incNumView(MultimediaObject $multimediaObject, Track $track = null)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $multimediaObject->incNumview();
        $track && $track->incNumview();
        $dm->persist($multimediaObject);
        $dm->flush();
    }

    protected function dispatch(MultimediaObject $multimediaObject, Track $track = null)
    {
        $event = new ViewedEvent($multimediaObject, $track);
        $this->get('event_dispatcher')->dispatch(BasePlayerEvents::MULTIMEDIAOBJECT_VIEW, $event);
    }

    protected function updateBreadcrumbs(MultimediaObject $multimediaObject)
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
        $breadcrumbs->addMultimediaObject($multimediaObject);
    }

    public function preExecute(MultimediaObject $multimediaObject, Request $request)
    {
        if ($opencasturl = $multimediaObject->getProperty('opencasturl')) {
            $this->incNumView($multimediaObject);
            $this->dispatch($multimediaObject);
            if ($invert = $multimediaObject->getProperty('opencastinvert')) {
                $opencasturl .= '&display=invert';
            }

            return $this->redirect($opencasturl);
        }
    }

    public function testBroadcast(MultimediaObject $multimediaObject, Request $request)
    {
        if (($broadcast = $multimediaObject->getBroadcast()) &&
          (Broadcast::BROADCAST_TYPE_PUB !== $broadcast->getBroadcastTypeId()) &&
          ((!($broadcastName = $request->headers->get('PHP_AUTH_USER', false))) ||
           ($request->headers->get('PHP_AUTH_PW') !== $broadcast->getPasswd()) ||
           ($broadcastName !== $broadcast->getName()))) {
            $seriesUrl = $this->generateUrl('pumukit_webtv_series_index', array('id' => $multimediaObject->getSeries()->getId()), true);
            $redReq = new RedirectResponse($seriesUrl, 302);

            return new Response($redReq->getContent(), 401, array('WWW-Authenticate' => 'Basic realm="Resource not public."'));
        }
        if ($broadcast && (Broadcast::BROADCAST_TYPE_PRI === $broadcast->getBroadcastTypeId())) {
            return new Response($this->render('PumukitWebTVBundle:Index:403forbidden.html.twig', array()), 403);
        }

        return true;
    }
}
