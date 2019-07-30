<?php

namespace Pumukit\NewAdminBundle\Controller;

use Pumukit\SchemaBundle\Document\Event;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Security("is_granted('ROLE_ACCESS_LIVE_EVENTS')")
 */
class LegacyEventController extends AdminController implements NewAdminControllerInterface
{
    public static $resourceName = 'event';
    public static $repoName = Event::class;

    /**
     * @var array
     */
    public static $daysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

    /**
     * Overwrite to get the calendar.
     *
     * @Template
     */
    public function indexAction(Request $request)
    {
        $criteria = $this->getCriteria($request->get('criteria', []));
        [$events, $month, $year, $calendar] = $this->getResources($request, $criteria);

        $update_session = true;
        foreach ($events as $event) {
            if ($event->getId() == $this->get('session')->get('admin/event/id')) {
                $update_session = false;
            }
        }

        if ($update_session) {
            $this->get('session')->remove('admin/event/id');
        }

        $repo = $this
            ->get('doctrine_mongodb.odm.document_manager')
            ->getRepository(Event::class)
        ;

        $eventsMonth = $repo->findInMonth($month, $year);

        return [
            'events' => $events,
            'calendar_all_events' => $eventsMonth,
            'm' => $month,
            'y' => $year,
            'calendar' => $calendar,
        ];
    }

    /**
     * Create Action
     * Overwrite to return json response
     * and update page.
     *
     * @param Request $request
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request)
    {
        $resource = $this->createNew();
        $form = $this->getForm($resource, $request->getLocale());

        if ($form->handleRequest($request)->isValid()) {
            $resource = $this->update($resource);

            if (null === $resource) {
                return new JsonResponse(['eventId' => null]);
            }
            $this->get('session')->set('admin/event/id', $resource->getId());

            return new JsonResponse(['eventId' => $resource->getId()]);
        }

        return $this->render(
            'PumukitNewAdminBundle:LegacyEvent:create.html.twig',
            [
                'event' => $resource,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * List action.
     *
     * @Template
     */
    public function listAction(Request $request)
    {
        $criteria = $this->getCriteria($request->get('criteria', []));
        [$events, $month, $year, $calendar] = $this->getResources($request, $criteria);

        $repo = $this
            ->get('doctrine_mongodb.odm.document_manager')
            ->getRepository(Event::class)
        ;

        $eventsMonth = $repo->findInMonth($month, $year);

        return [
            'events' => $events,
            'calendar_all_events' => $eventsMonth,
            'm' => $month,
            'y' => $year,
            'calendar' => $calendar,
        ];
    }

    /**
     * Update session with active tab.
     */
    public function updateSessionAction(Request $request)
    {
        $activeTab = $request->get('activeTab', null);

        if ($activeTab) {
            $this->get('session')->set('admin/event/tab', $activeTab);
            $tabValue = 'Active tab: '.$activeTab;
        } else {
            $this->get('session')->remove('admin/event/tab');
            $tabValue = 'Active tab: listTab';
        }

        return new JsonResponse(['tabValue' => $tabValue]);
    }

    /**
     * Overwrite to update the session.
     */
    public function showAction(Request $request)
    {
        $data = $this->findOr404($request);

        return $this->render(
            'PumukitNewAdminBundle:LegacyEvent:show.html.twig',
            [$this->getResourceName() => $data]
        );
    }

    /**
     * Update Action
     * Overwrite to return list and not index
     * and show toast message.
     *
     * @param Request $request
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function updateAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $resourceName = $this->getResourceName();

        $resource = $this->findOr404($request);
        $form = $this->getForm($resource, $request->getLocale());

        if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'])) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $dm->persist($resource);
                    $dm->flush();
                } catch (\Exception $e) {
                    return new JsonResponse(['status' => $e->getMessage()], 409);
                }

                return $this->redirect($this->generateUrl('pumukitnewadmin_'.$resourceName.'_list'));
            }
        }

        return $this->render(
            'PumukitNewAdminBundle:LegacyEvent:update.html.twig',
            [
                $resourceName => $resource,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Gets the criteria values.
     *
     * @param $criteria
     *
     * @return array
     */
    public function getCriteria($criteria)
    {
        if (array_key_exists('reset', $criteria)) {
            $this->get('session')->remove('admin/event/criteria');
        } elseif ($criteria) {
            $this->get('session')->set('admin/event/criteria', $criteria);
        }
        $criteria = $this->get('session')->get('admin/event/criteria', []);

        $new_criteria = [];

        $date_from = null;
        $date_to = null;

        foreach ($criteria as $property => $value) {
            //preg_match('/^\/.*?\/[imxlsu]*$/i', $e)
            if (('' !== $value) && ('date' !== $property)) {
                $new_criteria[$property] = new \MongoRegex('/'.$value.'/i');
            } elseif (('' !== $value) && ('date' == $property)) {
                if ('' !== $value['from']) {
                    $date_from = new \DateTime($value['from']);
                }
                if ('' !== $value['to']) {
                    $date_to = new \DateTime($value['to']);
                }
                if (('' !== $value['from']) && ('' !== $value['to'])) {
                    $new_criteria[$property] = ['$gte' => $date_from, '$lt' => $date_to];
                } elseif ('' !== $value['from']) {
                    $new_criteria[$property] = ['$gte' => $date_from];
                } elseif ('' !== $value['to']) {
                    $new_criteria[$property] = ['$lt' => $date_to];
                }
            }
        }

        return $new_criteria;
    }

    /**
     * Gets the list of resources according to a criteria.
     *
     * @param mixed $criteria
     */
    public function getResources(Request $request, $criteria)
    {
        $sorting = ['date' => -1];
        $session = $this->get('session');
        $session_namespace = 'admin/event';

        $page = $session->get($session_namespace.'/page', 1);

        $resources = $this->createPager($criteria, $sorting);

        if ($request->get('page', null)) {
            $page = $request->get('page');
            $session->set($session_namespace.'/page', $page);
        }

        // ADDED FROM ADMIN CONTROLLER
        if ($request->get('paginate', null)) {
            $session->set($session_namespace.'/paginate', $request->get('paginate', 10));
        }

        $resources
            ->setMaxPerPage(10)
            ->setNormalizeOutOfRangePages(true)
        ;

        $resources->setCurrentPage($page);

        [$m, $y, $calendar] = $this->getCalendar($request);

        return [$resources, $m, $y, $calendar];
    }

    public function createNew()
    {
        return new Event();
    }

    /**
     * Get calendar.
     *
     * @param mixed $request
     */
    private function getCalendar($request)
    {
        if (!$this->get('session')->get('admin/event/month')) {
            $this->get('session')->set('admin/event/month', date('m'));
        }
        if (!$this->get('session')->get('admin/event/year')) {
            $this->get('session')->set('admin/event/year', date('Y'));
        }

        $m = $this->get('session')->get('admin/event/month');
        $y = $this->get('session')->get('admin/event/year');

        if ('next' == $request->query->get('month')) {
            $changed_date = mktime(0, 0, 0, $m + 1, 1, $y);
            $this->get('session')->set('admin/event/year', date('Y', $changed_date));
            $this->get('session')->set('admin/event/month', date('m', $changed_date));
        } elseif ('previous' == $request->query->get('month')) {
            $changed_date = mktime(0, 0, 0, $m - 1, 1, $y);
            $this->get('session')->set('admin/event/year', date('Y', $changed_date));
            $this->get('session')->set('admin/event/month', date('m', $changed_date));
        } elseif ('today' == $request->query->get('month')) {
            $this->get('session')->set('admin/event/year', date('Y'));
            $this->get('session')->set('admin/event/month', date('m'));
        }

        $m = $this->get('session')->get('admin/event/month', date('m'));
        $y = $this->get('session')->get('admin/event/year', date('Y'));

        $calendar = $this->generateArray($m, $y);

        return [$m, $y, $calendar];
    }

    /**
     * Get days in month.
     *
     * @param mixed $month
     * @param mixed $year
     */
    private static function getDaysInMonth($month, $year)
    {
        if ($month < 1 || $month > 12) {
            return 0;
        }

        $d = self::$daysInMonth[$month - 1];

        if (2 == $month) {
            if (0 == $year % 4) {
                if (0 == $year % 100) {
                    if (0 == $year % 400) {
                        $d = 29;
                    }
                } else {
                    $d = 29;
                }
            }
        }

        return $d;
    }

    /**
     * Generate array.
     *
     * @param mixed $month
     * @param mixed $year
     */
    private static function generateArray($month, $year)
    {
        $aux = [];

        $dweek = date('N', mktime(0, 0, 0, $month, 1, $year)) - 1;
        foreach (range(1, self::getDaysInMonth($month, $year)) as $i) {
            $aux[(int) ($dweek / 7)][($dweek % 7)] = $i;
            ++$dweek;
        }

        return $aux;
    }
}
