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
        $translator = $this->get('translator');
        $this->updateBreadcrumbs($translator->trans('Live events'), 'pumukit_webtv_events');

        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $defaultPic = $this->container->getParameter('pumukitschema.default_video_pic');

        $eventsNow = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findEventsNow();
        $eventsToday = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findEventsToday();
        foreach ($eventsToday as $sKey => $event) {
            foreach ($event['data'] as $key => $sessionData) {
                $start = $sessionData['session']['start']->toDateTime();
                $ends = clone $start;
                $ends = $ends->add(new \DateInterval('PT'.($sessionData['session']['duration'] / 60).'M'));
                if (new \DateTime() > $start and new \DateTime() < $ends) {
                    unset($eventsToday[$sKey]);
                }
            }
        }
        $eventsFuture = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findNextEvents();

        return array('eventsToday' => $eventsToday, 'eventsNow' => $eventsNow, 'eventsFuture' => $eventsFuture, 'numberCols' => 2, 'defaultPic' => $defaultPic);
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
        $defaultPic = $this->container->getParameter('pumukitschema.default_video_pic');

        $events = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findNextEventSessions($id);

        return array('events' => $events, 'sessionlist' => true, 'defaultPic' => $defaultPic);
    }

    private function updateBreadcrumbs($title, $routeName, array $routeParameters = array())
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
        $breadcrumbs->addList($title, $routeName, $routeParameters);
    }
}
