<?php

namespace Pumukit\WebTVBundle\Controller;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class EventController extends Controller implements WebTVController
{
    /**
     * @return array()
     *
     * @Route ("/events/", defaults={"filter": false}, name="pumukit_webtv_events")
     * @Template()
     */
    public function indexAction()
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $defaultPic = $this->container->getParameter('pumukitschema.default_video_pic');

        $events = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findEventsGroupBy();
        foreach ($events as $key => $event) {
            if ($event['_id'] == 'past') {
                unset($events[$key]);
            }
        }

        return array('events' => $events, 'numberCols' => 2, 'defaultPic' => $defaultPic);
    }

    /**
     * @Template("PumukitWebTVBundle:Event:livelist.html.twig")
     */
    public function liveListAction()
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $defaultPic = $this->container->getParameter('pumukitschema.default_video_pic');

        $events = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findEventsNow();

        return array('events' => $events, 'defaultPic' => $defaultPic);
    }

    /**
     * @param string $id
     *
     * @return array
     *
     * @Route("/event/next/session/{id}", name="pumukit_webtv_next_session_event")
     * @Template("PumukitWebTVBundle:Event:nextsessionlist.html.twig")
     */
    public function nextSessionListAction($id)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        $events = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findNextEventSessions($id);

        return array('events' => $events, 'sessionlist' => true);
    }
}
