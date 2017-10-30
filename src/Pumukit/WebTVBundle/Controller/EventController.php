<?php

namespace Pumukit\WebTVBundle\Controller;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

class EventController extends Controller implements WebTVController
{
    /**
     * @param Request $request
     *
     * @return array
     *
     * @Route ("/events/", defaults={"filter": false}, name="pumukit_webtv_events")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $translator = $this->get('translator');
        $this->updateBreadcrumbs($translator->trans('Live events'), 'pumukit_webtv_events');

        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $defaultPic = $this->container->getParameter('pumukit_new_admin.advance_live_event_create_default_pic');

        $eventsNow = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findEventsNow();
        $eventsToday = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findEventsToday();
        $eventsToday = $this->getEventsTodayNextSession($eventsToday);
        $eventsFuture = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findNextEvents();

        $adapter = new ArrayAdapter($eventsFuture);
        $eventsFuture = new Pagerfanta($adapter);

        $page = $request->query->get('page', 1);

        $eventsFuture->setMaxPerPage(10);
        $eventsFuture->setNormalizeOutOfRangePages(true);
        $eventsFuture->setCurrentPage(intval($page));

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
        $defaultPic = $this->container->getParameter('pumukit_new_admin.advance_live_event_create_default_pic');

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
        $defaultPic = $this->container->getParameter('pumukit_new_admin.advance_live_event_create_default_pic');

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
        $result = array();
        foreach ($events as $event) {
            $multimediaObjectId = $event['_id'];
            $dm = $this->container->get('doctrine_mongodb')->getManager();

            $multimediaObject = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findOneBy(
                array('_id' => new \MongoId($multimediaObjectId))
            );

            $sessions = $multimediaObject->getEmbeddedEvent()->getEmbeddedEventSession();

            $todayEvents = array();
            $todayEvents['_id'] = $multimediaObjectId;

            $now = new \DateTime();
            $todayEnds = strtotime(date('Y-m-d H:i:s', mktime(23, 59, 59, date('m'), date('d'), date('Y'))));

            $nextSession = null;
            foreach ($sessions as $session) {
                if ($session->getStart()->getTimestamp() > $now->getTimestamp()) {
                    if ($session->getEnds()->getTimestamp() < $todayEnds) {
                        $nextSession = $session;
                        break;
                    }
                }
            }

            if (isset($nextSession)) {
                $data['event'] = $multimediaObject->getEmbeddedEvent();
                $data['session'] = $nextSession;
                $data['multimediaObjectId'] = $multimediaObjectId;
                if (isset($event['data'][0]['pics'])) {
                    $data['pics'] = $event['data'][0]['pics'];
                } else {
                    $data['pics'] = array();
                }

                $todayEvents['data'][] = $data;

                $result[] = $todayEvents;
            }
        }

        return $result;
    }
}
