<?php

namespace Pumukit\StatsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Pumukit\NewAdminBundle\Controller\NewAdminControllerInterface;

/**
 * @Route("/api/media")
 */
class APIController extends Controller implements NewAdminControllerInterface
{
    /**
     * @Route("/mmobj/most_viewed.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function mmobjMostViewedAction(Request $request)
    {
        $serializer = $this->get('jms_serializer');
        $viewsService = $this->get('pumukit_stats.stats');

        list($criteria, $sort, $fromDate, $toDate, $limit, $page) = $this->processRequestData($request);

        $options['from_date'] = $fromDate;
        $options['to_date'] = $toDate;
        $options['limit'] = $limit;
        $options['page'] = $page;
        $options['sort'] = $sort;

        list($mmobjs, $total) = $viewsService->getMmobjsMostViewedByRange($criteria, $options);

        $views = [
            'limit' => $limit,
            'page' => $page,
            'total' => $total,
            'criteria' => $criteria,
            'sort' => $sort,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'mmobjs' => $mmobjs,
        ];

        $data = $serializer->serialize($views, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/series/most_viewed.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function seriesMostViewedAction(Request $request)
    {
        $serializer = $this->get('jms_serializer');
        $viewsService = $this->get('pumukit_stats.stats');

        list($criteria, $sort, $fromDate, $toDate, $limit, $page) = $this->processRequestData($request);

        $options['from_date'] = $fromDate;
        $options['to_date'] = $toDate;
        $options['limit'] = $limit;
        $options['page'] = $page;
        $options['sort'] = $sort;

        list($series, $total) = $viewsService->getSeriesMostViewedByRange($criteria, $options);

        $views = [
            'limit' => $limit,
            'page' => $page,
            'total' => $total,
            'criteria' => $criteria,
            'sort' => $sort,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'series' => $series,
        ];

        $data = $serializer->serialize($views, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/views.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function viewsAction(Request $request)
    {
        $serializer = $this->get('jms_serializer');
        $viewsService = $this->get('pumukit_stats.stats');

        list($criteria, $sort, $fromDate, $toDate, $limit, $page) = $this->processRequestData($request);

        $groupBy = $request->get('group_by') ?: 'month';

        //NOTE: $criteria is the same as $criteria_mmobj to provide backwards compatibility.
        $criteria_mmobj = $request->get('criteria_mmobj') ?: $criteria;
        $criteria_series = $request->get('criteria_series') ?: [];

        $options['from_date'] = $fromDate;
        $options['to_date'] = $toDate;
        $options['limit'] = $limit;
        $options['page'] = $page;
        $options['sort'] = $sort;
        $options['group_by'] = $groupBy;
        $options['criteria_mmobj'] = $criteria_mmobj;
        $options['criteria_series'] = $criteria_series;

        list($views, $total) = $viewsService->getTotalViewedGrouped($options);

        $views = [
            'limit' => $limit,
            'page' => $page,
            'total' => $total,
            'criteria' => [
                'criteria_mmobj' => $criteria_mmobj,
                'criteria_series' => $criteria_series,
            ],
            'sort' => $sort,
            'group_by' => $groupBy,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'views' => $views,
        ];

        $data = $serializer->serialize($views, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/views/mmobj.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function viewsMmobjAction(Request $request)
    {
        $serializer = $this->get('jms_serializer');
        $viewsService = $this->get('pumukit_stats.stats');

        $mmobjId = $request->get('mmobj');

        list($criteria, $sort, $fromDate, $toDate, $limit, $page) = $this->processRequestData($request);

        $groupBy = $request->get('group_by') ?: 'month';

        $options['from_date'] = $fromDate;
        $options['to_date'] = $toDate;
        $options['limit'] = $limit;
        $options['page'] = $page;
        $options['sort'] = $sort;
        $options['group_by'] = $groupBy;

        list($views, $total) = $viewsService->getTotalViewedGroupedByMmobj(new \MongoId($mmobjId), $options);

        $views = [
            'limit' => $limit,
            'page' => $page,
            'total' => $total,
            'sort' => $sort,
            'group_by' => $groupBy,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'mmobj_id' => $mmobjId ?: -1,
            'views' => $views,
        ];

        $data = $serializer->serialize($views, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/views/series.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function viewsSeriesAction(Request $request)
    {
        $serializer = $this->get('jms_serializer');
        $viewsService = $this->get('pumukit_stats.stats');

        $seriesId = $request->get('series');

        list($criteria, $sort, $fromDate, $toDate, $limit, $page) = $this->processRequestData($request);

        $groupBy = $request->get('group_by') ?: 'month';

        $options['from_date'] = $fromDate;
        $options['to_date'] = $toDate;
        $options['limit'] = $limit;
        $options['page'] = $page;
        $options['sort'] = $sort;
        $options['group_by'] = $groupBy;

        list($views, $total) = $viewsService->getTotalViewedGroupedBySeries(new \MongoId($seriesId), $options);

        $views = [
            'limit' => $limit,
            'page' => $page,
            'total' => $total,
            'sort' => $sort,
            'group_by' => $groupBy,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'series_id' => $seriesId ?: -1,
            'views' => $views,
        ];

        $data = $serializer->serialize($views, $request->getRequestFormat());

        return new Response($data);
    }

    protected function processRequestData(Request $request)
    {
        $MAX_LIMIT = 1000;
        //Request variables.
        $criteria = $request->get('criteria') ?: [];
        $sort = intval($request->get('sort'));
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $limit = intval($request->get('limit'));
        $page = intval($request->get('page')) ?: 0;

        //Processing variables.
        if (!$limit || $limit > $MAX_LIMIT) {
            $limit = $MAX_LIMIT;
        }

        if (!in_array($sort, [1, -1])) {
            $sort = -1;
        }

        if (!strpos($fromDate, 'T')) {
            $fromDate .= 'T00:00:00';
        }
        if (!strpos($toDate, 'T')) {
            $toDate .= 'T23:59:59';
        }
        $fromDate = \DateTime::createFromFormat('Y-m-d\TH:i:s', $fromDate) ?: null;
        $toDate = \DateTime::createFromFormat('Y-m-d\TH:i:s', $toDate) ?: null;

        return [$criteria, $sort, $fromDate, $toDate, $limit, $page];
    }
}
