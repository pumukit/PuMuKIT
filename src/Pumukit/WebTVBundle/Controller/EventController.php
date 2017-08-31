<?php

namespace Pumukit\WebTVBundle\Controller;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

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

        $events = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findEventsGroupBy();
        foreach ($events as $key => $event) {
            if ($event['_id'] == 'past') {
                unset($events[$key]);
            }
        }

        return array('events' => $events, 'numberCols' => 2);
    }

    /**
     * @Template("PumukitWebTVBundle:Event:livelist.html.twig")
     */
    public function liveListAction()
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        $events = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findEventsNow();

        return array('events' => $events);
    }

    /**
     * @param MultimediaObject $multimediaObject
     *
     * @return array
     *
     * @Route("/event/next/session/{id}", name="pumukit_webtv_next_session_event")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"mapping": {"id": "id"}})
     * @Template("PumukitWebTVBundle:Event:nextsessionlist.html.twig")
     */
    public function nextSessionListAction(MultimediaObject $multimediaObject)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        $events = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findNextEventSessions($multimediaObject->getId());

        return array('events' => $events, 'sessionlist' => true);
    }
}
