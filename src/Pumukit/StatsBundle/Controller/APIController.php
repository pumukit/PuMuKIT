<?php

namespace Pumukit\StatsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/api/media")
 */
class APIController extends Controller
{
    private $MAX_LIMIT = 500;

    /**
     * @Route("/mmobj/most_viewed.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function mmobjMostViewedAction(Request $request)
    {
        $viewsRepo = $this->get('doctrine_mongodb')->getRepository('PumukitStatsBundle:ViewsLog');
        $serializer = $this->get('serializer');
        $viewsService = $this->get('pumukit_stats.stats');

        list($criteria, $sort, $fromDate, $toDate, $limit, $page) = $this->processRequestData($request);

        $mmobjs = $viewsService->getMmobjsMostViewedByRange($fromDate, $toDate, $limit, $criteria, $sort);

        $views = array(
            'limit' => $limit,
            'criteria' => $criteria,
            'sort' => $sort,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'mmobjs' => $mmobjs,
        );

        $data = $serializer->serialize($views, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/series/most_viewed.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function seriesMostViewedAction(Request $request)
    {
        $viewsRepo = $this->get('doctrine_mongodb')->getRepository('PumukitStatsBundle:ViewsLog');
        $serializer = $this->get('serializer');
        $viewsService = $this->get('pumukit_stats.stats');

        list($criteria, $sort, $fromDate, $toDate, $limit, $page) = $this->processRequestData($request);

        $series = $viewsService->getSeriesMostViewedByRange($fromDate, $toDate, $limit, $criteria, $sort);

        $views = array(
            'limit' => $limit,
            'page' => $page,
            'criteria' => $criteria,
            'sort' => $sort,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'series' => $series,
        );

        $data = $serializer->serialize($views, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/views.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function viewsAction(Request $request)
    {
        $viewsRepo = $this->get('doctrine_mongodb')->getRepository('PumukitStatsBundle:ViewsLog');
        $serializer = $this->get('serializer');
        $viewsService = $this->get('pumukit_stats.stats');

        list($criteria, $sort, $fromDate, $toDate, $limit, $page) = $this->processRequestData($request);

        $groupBy = $request->get('group_by') ?: 'month';

        $views = $viewsService->getTotalViewedGrouped($fromDate, $toDate, $limit, $criteria, $sort, $groupBy);

        $views = array(
            'limit' => $limit,
            'criteria' => $criteria,
            'sort' => $sort,
            'group_by' => $groupBy,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'views' => $views,
        );

        $data = $serializer->serialize($views, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/views/mmobj.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function viewsMmobjAction(Request $request)
    {
        $viewsRepo = $this->get('doctrine_mongodb')->getRepository('PumukitStatsBundle:ViewsLog');
        $serializer = $this->get('serializer');
        $viewsService = $this->get('pumukit_stats.stats');

        $mmobjId = $request->get('mmobj');

        list($criteria, $sort, $fromDate, $toDate, $limit, $page) = $this->processRequestData($request);

        $groupBy = $request->get('group_by');

        $views = $viewsService->getTotalViewedGroupedByMmobj(new \MongoId($mmobjId), $fromDate, $toDate, $limit, $criteria, $sort, $groupBy);

        $views = array(
            'limit' => $limit,
            'criteria' => $criteria,
            'sort' => $sort,
            'group_by' => $groupBy,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'mmobj_id' => $mmobjId ?: -1,
            'views' => $views,
        );

        $data = $serializer->serialize($views, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/views/series.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function viewsSeriesAction(Request $request)
    {
        $viewsRepo = $this->get('doctrine_mongodb')->getRepository('PumukitStatsBundle:ViewsLog');
        $serializer = $this->get('serializer');
        $viewsService = $this->get('pumukit_stats.stats');

        $seriesId = $request->get('series');

        list($criteria, $sort, $fromDate, $toDate, $limit, $page) = $this->processRequestData($request);

        $groupBy = $request->get('group_by') ?: 'month';

        $views = $viewsService->getTotalViewedGroupedBySeries(new \MongoId($seriesId), $fromDate, $toDate, $limit, $criteria, $sort, $groupBy);

        $views = array(
            'limit' => $limit,
            'criteria' => $criteria,
            'sort' => $sort,
            'group_by' => $groupBy,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'series_id' => $seriesId ?: -1,
            'views' => $views,
        );

        $data = $serializer->serialize($views, $request->getRequestFormat());

        return new Response($data);
    }

    protected function processRequestData(Request $request)
    {
        //Request variables.
        $criteria = $request->get('criteria') ?: array();
        $sort = intval($request->get('sort'));
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $limit = intval($request->get('limit'));
        $page = $request->get('page') ?: 0;

        //Processing variables.
        if (!$limit || $limit > $this->MAX_LIMIT) {
            $limit = $this->MAX_LIMIT;
        }

        if (!in_array($sort, array(1, -1))) {
            $sort = -1;
        }

        if (!strpos($fromDate, 'T')) {
            $fromDate .= 'T00:00:00';
        }
        if (!strpos($toDate, 'T')) {
            $toDate .= 'T23:59:59';
        }
        $fromDate = \DateTime::createFromFormat('Y-m-d\TH:i:s', $fromDate);
        $toDate = \DateTime::createFromFormat('Y-m-d\TH:i:s', $toDate);
        if (!$fromDate) {
            $fromDate = new \DateTime('Z');
            $fromDate->setTime(0, 0, 0);
        }
        if (!$toDate) {
            $toDate = new \DateTime('Z');
        }

        return array($criteria, $sort, $fromDate, $toDate, $limit, $page);
    }
}
