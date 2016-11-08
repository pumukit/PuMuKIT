<?php

namespace Pumukit\BasePlayerBundle\Controller;

use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Pumukit\BasePlayerBundle\Event\BasePlayerEvents;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class TrackFileController extends Controller
{
    /**
     * @Route("/trackfile/{id}.{ext}", name="pumukit_trackfile_index" )
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
        return $this->redirect($track->getUrl());
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
