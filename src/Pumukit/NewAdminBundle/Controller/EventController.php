<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Security("is_granted('ROLE_ACCESS_LIVE_EVENTS')")
 */
class EventController extends AdminController implements NewAdminController
{
    /**
     * @var array
     */
    public static $daysInMonth = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

    /**
     * Overwrite to get the calendar.
     *
     * @Template
     */
    public function indexAction(Request $request)
    {
        $config = $this->getConfiguration();

        $criteria = $this->getCriteria($config);
        list($events, $month, $year, $calendar) = $this->getResources($request, $config, $criteria);

        $update_session = true;
        foreach ($events as $event) {
            if ($event->getId() == $this->get('session')->get('admin/event/id')) {
                $update_session = false;
            }
        }

        if ($update_session) {
            $this->get('session')->remove('admin/event/id');
        }

        return array(
                     'events' => $events,
                     'm' => $month,
                     'y' => $year,
                     'calendar' => $calendar,
                     );
    }

    /**
     * Create Action
     * Overwrite to return json response
     * and update page.
     *
     * @param Request $request
     *
     * @return JsonResponse|Response
     */
    public function createAction(Request $request)
    {
        $config = $this->getConfiguration();

        $resource = $this->createNew();
        $form = $this->getForm($resource);

        if ($form->handleRequest($request)->isValid()) {
            $resource = $this->domainManager->create($resource);

            if ($this->config->isApiRequest()) {
                return $this->handleView($this->view($resource, 201));
            }

            if (null === $resource) {
                return new JsonResponse(array('eventId' => null));
            }
            $this->get('session')->set('admin/event/id', $resource->getId());

            return new JsonResponse(array('eventId' => $resource->getId()));
        }

        if ($this->config->isApiRequest()) {
            return $this->handleView($this->view($form));
        }

        return $this->render('PumukitNewAdminBundle:Event:create.html.twig',
                             array(
                                   'event' => $resource,
                                   'form' => $form->createView(),
                                   ));
    }

    /**
     * List action.
     *
     * @Template
     */
    public function listAction(Request $request)
    {
        $config = $this->getConfiguration();

        $sorting = $request->get('sorting');

        $criteria = $this->getCriteria($config);
        list($events, $month, $year, $calendar) = $this->getResources($request, $config, $criteria);

        return array(
                     'events' => $events,
                     'm' => $month,
                     'y' => $year,
                     'calendar' => $calendar,
                     );
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

        return new JsonResponse(array('tabValue' => $tabValue));
    }

    /**
     * Get calendar.
     */
    private function getCalendar($config, $request)
    {
        /*if (!$this->getUser()->hasAttribute('page', 'tv_admin/event'))
          $this->getUser()->setAttribute('page', 1, 'tv_admin/event');*/

        if (!$this->get('session')->get('admin/event/month')) {
            $this->get('session')->set('admin/event/month', date('m'));
        }
        if (!$this->get('session')->get('admin/event/year')) {
            $this->get('session')->set('admin/event/year', date('Y'));
        }

        $m = $this->get('session')->get('admin/event/month');
        $y = $this->get('session')->get('admin/event/year');

        if ($request->query->get('month') == 'next') {
            $changed_date = mktime(0, 0, 0, $m + 1, 1, $y);
            $this->get('session')->set('admin/event/year', date('Y', $changed_date));
            $this->get('session')->set('admin/event/month', date('m', $changed_date));
        } elseif ($request->query->get('month') == 'previous') {
            $changed_date = mktime(0, 0, 0, $m - 1, 1, $y);
            $this->get('session')->set('admin/event/year', date('Y', $changed_date));
            $this->get('session')->set('admin/event/month', date('m', $changed_date));
        } elseif ($request->query->get('month') == 'today') {
            $this->get('session')->set('admin/event/year', date('Y'));
            $this->get('session')->set('admin/event/month', date('m'));
        }

        $m = $this->get('session')->get('admin/event/month', date('m'));
        $y = $this->get('session')->get('admin/event/year', date('Y'));

        $calendar = $this->generateArray($m, $y);

        return array($m, $y, $calendar);
    }

    /**
     * Get days in month.
     */
    private static function getDaysInMonth($month, $year)
    {
        if ($month < 1 || $month > 12) {
            return 0;
        }

        $d = self::$daysInMonth[$month - 1];

        if ($month == 2) {
            if ($year % 4 == 0) {
                if ($year % 100 == 0) {
                    if ($year % 400 == 0) {
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
     */
    private static function generateArray($month, $year)
    {
        $aux = array();

        $dweek = date('N', mktime(0, 0, 0, $month, 1, $year)) - 1;
        foreach (range(1, self::getDaysInMonth($month, $year)) as $i) {
            $aux[intval($dweek / 7)][($dweek % 7)] = $i;
            ++$dweek;
        }

        return $aux;
    }

    /**
     * Gets the criteria values.
     */
    public function getCriteria($config)
    {
        $criteria = $config->getCriteria();

        if (array_key_exists('reset', $criteria)) {
            $this->get('session')->remove('admin/event/criteria');
        } elseif ($criteria) {
            $this->get('session')->set('admin/event/criteria', $criteria);
        }
        $criteria = $this->get('session')->get('admin/event/criteria', array());

        $new_criteria = array();

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
                    $new_criteria[$property] = array('$gte' => $date_from, '$lt' => $date_to);
                } elseif ('' !== $value['from']) {
                    $new_criteria[$property] = array('$gte' => $date_from);
                } elseif ('' !== $value['to']) {
                    $new_criteria[$property] = array('$lt' => $date_to);
                }
            }
        }

        return $new_criteria;
    }

    /**
     * Gets the list of resources according to a criteria.
     */
    public function getResources(Request $request, $config, $criteria)
    {
        $sorting = $config->getSorting();
        $repository = $this->getRepository();
        $session = $this->get('session');
        $session_namespace = 'admin/event';

        $newEventId = $request->get('newEventId');
        $page = $session->get($session_namespace.'/page', 1);

        $m = '';
        $y = '';
        $calendar = array();

        if ($config->isPaginated()) {
            $resources = $this
                ->resourceResolver
                ->getResource($repository, 'createPaginator', array($criteria, $sorting));

            if ($request->get('page', null)) {
                $session->set($session_namespace.'/page', $request->get('page', 1));
            }

            // ADDED FROM ADMIN CONTROLLER
            if ($request->get('paginate', null)) {
                $session->set($session_namespace.'/paginate', $request->get('paginate', 10));
            }

            $resources
                ->setMaxPerPage($config->getPaginationMaxPerPage())
                ->setNormalizeOutOfRangePages(true);

            if ($newEventId && (($resources->getNbResults() / $resources->getMaxPerPage()) > $page)) {
                $page = $resources->getNbPages();
                $session->set($session_namespace.'/page', $page);
            }
            $resources->setCurrentPage($page);
        } else {
            $resources = $this
                ->resourceResolver
                ->getResource($repository, 'findBy', array($criteria, $sorting, $config->getLimit()));
        }

        list($m, $y, $calendar) = $this->getCalendar($config, $request);

        return array($resources, $m, $y, $calendar);
    }
}
