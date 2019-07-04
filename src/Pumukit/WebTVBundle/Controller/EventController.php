<?php

namespace Pumukit\WebTVBundle\Controller;

use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class EventController.
 */
class EventController extends Controller implements WebTVControllerInterface
{
    /**
     * @Route ("/events/", defaults={"filter": false}, name="pumukit_webtv_events")
     * @Template("PumukitWebTVBundle:Live:template.html.twig")
     *
     * @param Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $advanceEvents = $this->checkAdvanceEvents();
        if (!$advanceEvents) {
            return $this->render('PumukitWebTVBundle:Index:404notfound.html.twig');
        }

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

        $maxPerPage = $this->container->getParameter('columns_objs_event') * 3;
        $eventsFuture->setMaxPerPage($maxPerPage);
        $eventsFuture->setNormalizeOutOfRangePages(true);
        $eventsFuture->setCurrentPage((int) $page);

        return [
            'eventsToday' => $eventsToday,
            'eventsNow' => $eventsNow,
            'eventsFuture' => $eventsFuture,
            'defaultPic' => $defaultPic,
            'objectByCol' => $this->container->getParameter('columns_objs_event'),
            'show_info' => true,
            'show_description' => false,
        ];
    }

    /**
     * @Template("PumukitWebTVBundle:Live:Advance/livelist.html.twig")
     */
    public function liveListAction()
    {
        $defaultPic = $this->container->getParameter('pumukit_new_admin.advance_live_event_create_default_pic');
        $events = $this->get('pumukitschema.eventsession')->findEventsNow();

        return [
            'events' => $events,
            'defaultPic' => $defaultPic,
        ];
    }

    /**
     * @param string $id
     *
     * @return array
     * @Route("/event/next/session/{id}", name="pumukit_webtv_next_session_event")
     * @Template("PumukitWebTVBundle:Live:Advance/nextsessionlist.html.twig")
     */
    public function nextSessionListAction($id)
    {
        $defaultPic = $this->container->getParameter('pumukit_new_admin.advance_live_event_create_default_pic');
        $embeddedEventSessionService = $this->get('pumukitschema.eventsession');

        $criteria = [
            '_id' => new \MongoId($id),
        ];
        $events = $embeddedEventSessionService->findNextSessions($criteria, 0, true);

        return [
            'events' => $events,
            'sessionlist' => true,
            'defaultPic' => $defaultPic,
        ];
    }

    /**
     * @param string $id
     *
     * @return array
     * @Route("/event/twitter/{id}", name="pumukit_webtv_event_twitter")
     * @Template("PumukitWebTVBundle:Live:Advance/twitter.html.twig")
     */
    public function twitterAction($id)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $repo = $dm->getRepository(MultimediaObject::class);
        $multimediaObject = $repo->find($id);
        $enableTwitter = $this->container->getParameter('pumukit_live.twitter.enable');

        return [
            'multimediaObject' => $multimediaObject,
            'enable_twitter' => $enableTwitter,
        ];
    }

    /**
     * @param       $title
     * @param       $routeName
     * @param array $routeParameters
     */
    private function updateBreadcrumbs($title, $routeName, array $routeParameters = [])
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
        $breadcrumbs->addList($title, $routeName, $routeParameters);
    }

    /**
     * @param $eventsNow
     * @param $eventsToday
     *
     * @return array
     */
    private function getEventsTodayNextSession($eventsNow, $eventsToday)
    {
        $now = array_map(
            function ($e) {
                return $e['_id'];
            },
            $eventsNow
        );

        $result = [];
        foreach ($eventsToday as $event) {
            if (in_array($event['_id'], $now)) {
                continue;
            }
            $result[] = $event;
        }

        return $result;
    }

    /**
     * @return mixed
     */
    private function checkAdvanceEvents()
    {
        return $this->container->getParameter('pumukit_new_admin.advance_live_event');
    }
}
