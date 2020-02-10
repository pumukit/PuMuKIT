<?php

namespace Pumukit\StatsBundle\Controller;

use MongoDB\BSON\ObjectId;
use Pumukit\CoreBundle\Services\SerializerService;
use Pumukit\NewAdminBundle\Controller\NewAdminControllerInterface;
use Pumukit\StatsBundle\Services\StatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/media")
 */
class APIController extends AbstractController implements NewAdminControllerInterface
{
    /** @var SerializerService */
    private $serializerService;

    /** @var StatsService */
    private $statsService;

    public function __construct(SerializerService $serializerService, StatsService $statsService)
    {
        $this->serializerService = $serializerService;
        $this->statsService = $statsService;
    }

    /**
     * @Route("/mmobj/most_viewed.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function mmobjMostViewedAction(Request $request)
    {
        [$criteria, $sort, $fromDate, $toDate, $limit, $page] = $this->processRequestData($request);

        $options['from_date'] = $fromDate;
        $options['to_date'] = $toDate;
        $options['limit'] = $limit;
        $options['page'] = $page;
        $options['sort'] = $sort;

        [$mmobjs, $total] = $this->statsService->getMmobjsMostViewedByRange($criteria, $options);

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

        $data = $this->serializerService->dataSerialize($views, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/series/most_viewed.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function seriesMostViewedAction(Request $request)
    {
        [$criteria, $sort, $fromDate, $toDate, $limit, $page] = $this->processRequestData($request);

        $options['from_date'] = $fromDate;
        $options['to_date'] = $toDate;
        $options['limit'] = $limit;
        $options['page'] = $page;
        $options['sort'] = $sort;

        [$series, $total] = $this->statsService->getSeriesMostViewedByRange($criteria, $options);

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

        $data = $this->serializerService->dataSerialize($views, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/views.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function viewsAction(Request $request)
    {
        [$criteria, $sort, $fromDate, $toDate, $limit, $page] = $this->processRequestData($request);

        $groupBy = $request->get('group_by') ?: 'month';

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

        [$views, $total] = $this->statsService->getTotalViewedGrouped($options);

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

        $data = $this->serializerService->dataSerialize($views, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/views/mmobj.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function viewsMmobjAction(Request $request)
    {
        $mmobjId = $request->get('mmobj');

        [$criteria, $sort, $fromDate, $toDate, $limit, $page] = $this->processRequestData($request);

        $groupBy = $request->get('group_by') ?: 'month';

        $options['from_date'] = $fromDate;
        $options['to_date'] = $toDate;
        $options['limit'] = $limit;
        $options['page'] = $page;
        $options['sort'] = $sort;
        $options['group_by'] = $groupBy;

        [$views, $total] = $this->statsService->getTotalViewedGroupedByMmobj(new ObjectId($mmobjId), $options);

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

        $data = $this->serializerService->dataSerialize($views, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/views/series.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function viewsSeriesAction(Request $request)
    {
        $seriesId = $request->get('series');

        [$criteria, $sort, $fromDate, $toDate, $limit, $page] = $this->processRequestData($request);

        $groupBy = $request->get('group_by') ?: 'month';

        $options['from_date'] = $fromDate;
        $options['to_date'] = $toDate;
        $options['limit'] = $limit;
        $options['page'] = $page;
        $options['sort'] = $sort;
        $options['group_by'] = $groupBy;

        [$views, $total] = $this->statsService->getTotalViewedGroupedBySeries(new ObjectId($seriesId), $options);

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

        $data = $this->serializerService->dataSerialize($views, $request->getRequestFormat());

        return new Response($data);
    }

    protected function processRequestData(Request $request)
    {
        $MAX_LIMIT = 1000;
        //Request variables.
        $criteria = $request->get('criteria') ?: [];
        $sort = (int) ($request->get('sort'));
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $limit = (int) ($request->get('limit'));
        $page = (int) ($request->get('page')) ?: 0;

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
