<?php

namespace Pumukit\WebTVBundle\Controller;

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

        $defaultPic = $this->container->getParameter('pumukit_new_admin.advance_live_event_create_default_pic');

        $eventsNow = $this->get('pumukitschema.eventsession')->findEventsNow();
        $eventsToday = $this->get('pumukitschema.eventsession')->findEventsToday();
        $eventsToday = $this->getEventsTodayNextSession($eventsNow, $eventsToday);
        $eventsFuture = $this->get('pumukitschema.eventsession')->findNextEvents();

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

        $events = $this->get('pumukitschema.eventsession')->findEventsNow();

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

    private function getEventsTodayNextSession($eventsNow, $eventsToday)
    {
        $now = array_map(function ($e) {
            return $e['_id'];
        }, $eventsNow);

        $result = array();
        foreach ($eventsToday as $event) {
            if (in_array($event['_id'], $now)) {
                continue;
            }
            $result[] = $event;
        }

        return $result;
    }
}
