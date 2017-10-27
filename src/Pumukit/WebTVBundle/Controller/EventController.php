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
     * @Route ("/events/", defaults={"filter": false}, name="pumukit_webtv_events")
     * @Template()
     */
    public function indexAction()
    {
        $translator = $this->get('translator');
        $this->updateBreadcrumbs($translator->trans('Live events'), 'pumukit_webtv_events');

        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $defaultPic = $this->container->getParameter('pumukitschema.default_video_pic');

        $eventsNow = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findEventsNow();
        $eventsToday = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findEventsToday();
        $eventsToday = $this->getEventsTodayNextSession($eventsToday);
        $eventsFuture = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findNextEvents();

        return array(
            'eventsToday' => $eventsToday,
            'eventsNow' => $eventsNow,
            'eventsFuture' => $eventsFuture,
            'numberCols' => 2,
            'defaultPic' => $defaultPic,
        );
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
     * @Route("/event/next/session/{id}", name="pumukit_webtv_next_session_event")
     * @Template("PumukitWebTVBundle:Event:nextsessionlist.html.twig")
     */
    public function nextSessionListAction($id)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $defaultPic = $this->container->getParameter('pumukitschema.default_video_pic');

        $events = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findNextEventSessions($id);

        return array('events' => $events, 'sessionlist' => true, 'defaultPic' => $defaultPic);
    }

    /**
     * @param       $title
     * @param       $routeName
     * @param array $routeParameters
     */
    private function updateBreadcrumbs($title, $routeName, array $routeParameters = array())
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
        $breadcrumbs->addList($title, $routeName, $routeParameters);
    }

    private function getEventsTodayNextSession($events)
    {
        $events = $events[0];
        $multimediaObjectId = $events['_id'];
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        $multimediaObject = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findOneBy(array('_id' => new \MongoId($multimediaObjectId)));

        $sessions = $multimediaObject->getEmbeddedEvent()->getEmbeddedEventSession();

        $todayEvents = array();
        $todayEvents['_id'] = $multimediaObjectId;

        $now = new \DateTime();
        foreach ($sessions as $session) {
            if ($session->getStart()->getTimestamp() > $now->getTimestamp()) {
                $nextSession = $session;
                break;
            }
        }

        $data['event'] = $multimediaObject->getEmbeddedEvent();
        $data['session'] = $nextSession;
        $data['multimediaObjectId'] = $multimediaObjectId;

        $todayEvents['data'][] = $data;

        $result[] = $todayEvents;

        return $result;
    }
}
