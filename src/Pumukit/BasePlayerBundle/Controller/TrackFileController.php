<?php

namespace Pumukit\BasePlayerBundle\Controller;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Pumukit\BasePlayerBundle\Event\BasePlayerEvents;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class TrackFileController extends Controller
{
    /**
     * @Route("/trackfile/{id}.{ext}", name="pumukit_trackfile_index" )
     * @Route("/trackfile/{id}", name="pumukit_trackfile_index_no_ext" )
     */
    public function indexAction($id, Request $request)
    {
        $mmobjRepo = $this
          ->get('doctrine_mongodb.odm.document_manager')
          ->getRepository('PumukitSchemaBundle:MultimediaObject');

        $mmobj = $mmobjRepo->findOneByTrackId($id);
        if (!$mmobj) {
            throw $this->createNotFoundException("Not mmobj found with the track id: $id");
        }
        $track = $mmobj->getTrackById($id);

        if ($this->shouldIncreaseViews($track, $request)) {
            $this->dispatchViewEvent($mmobj, $track);
        }

        if ($secret = $this->container->getParameter('pumukitplayer.secure_secret')) {
            $timestamp = time() + $this->container->getParameter('pumukitplayer.secure_duration');
            $hash = $this->getHash($track, $timestamp, $secret, $request->getClientIp());

            return $this->redirect($track->getUrl()."?md5=${hash}&expires=${timestamp}&".http_build_query($request->query->all(), null, '&'));
        }

        if ($request->query->all()) {
            return $this->redirect($track->getUrl().'?'.http_build_query($request->query->all()));
        } else {
            return $this->redirect($track->getUrl());
        }
    }

    protected function getHash(Track $track, $timestamp, $secret, $ip)
    {
        $url = $track->getUrl();
        $path = parse_url($url, PHP_URL_PATH);

        return str_replace('=', '', strtr(base64_encode(md5("${timestamp}${path}${ip} ${secret}", true)), '+/', '-_'));
    }

    protected function shouldIncreaseViews(Track $track, Request $request)
    {
        $range = $request->headers->get('range');
        $start = $request->headers->get('start');
        if (!$range && !$start) {
            return true;
        }
        if ($range && substr($range, 0, 8) == 'bytes=0-') {
            return true;
        }
        if ($start !== null && $start == 0) {
            return true;
        }

        return false;
    }

    protected function dispatchViewEvent(MultimediaObject $multimediaObject, Track $track = null)
    {
        $event = new ViewedEvent($multimediaObject, $track);
        $this->get('event_dispatcher')->dispatch(BasePlayerEvents::MULTIMEDIAOBJECT_VIEW, $event);
    }
}
