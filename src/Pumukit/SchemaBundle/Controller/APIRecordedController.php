<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Controller;

use Pumukit\CoreBundle\Services\SerializerService;
use Pumukit\SchemaBundle\Services\StatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/media")
 */
class APIRecordedController extends AbstractController
{
    /**
     * @Route("/mmobj/num_recorded.{_format}", defaults={"_format"="json"}, requirements={"_format"="json|xml"})
     * @Route("/mmobj/recorded.{_format}", defaults={"_format"="json"}, requirements={"_format"="json|xml"})
     */
    public function mmobjRecordedAction(Request $request, StatsService $recordsService, SerializerService $serializer)
    {
        [$criteria, $sort, $fromDate, $toDate, $limit, $page] = $this->processRequestData($request);

        $groupBy = $request->get('group_by') ?: 'month';

        $views = $recordsService->getMmobjRecordedGroupedBy($fromDate, $toDate, $limit, $page, $criteria, $sort, $groupBy);

        $views = [
            'limit' => $limit,
            'page' => $page,
            'criteria' => $criteria,
            'sort' => $sort,
            'group_by' => $groupBy,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'views' => $views,
        ];

        $data = $serializer->dataSerialize($views, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/series/num_recorded.{_format}", defaults={"_format"="json"}, requirements={"_format"="json|xml"})
     * @Route("/series/recorded.{_format}", defaults={"_format"="json"}, requirements={"_format"="json|xml"})
     * @Route("/series/published.{_format}", defaults={"_format"="json"}, requirements={"_format"="json|xml"})
     */
    public function seriesRecordedAction(Request $request, StatsService $recordsService, SerializerService $serializer)
    {
        [$criteria, $sort, $fromDate, $toDate, $limit, $page] = $this->processRequestData($request);

        $groupBy = $request->get('group_by') ?: 'month';

        $views = $recordsService->getSeriesRecordedGroupedBy($fromDate, $toDate, $limit, $page, $criteria, $sort, $groupBy);

        $views = [
            'limit' => $limit,
            'page' => $page,
            'criteria' => $criteria,
            'sort' => $sort,
            'group_by' => $groupBy,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'views' => $views,
        ];

        $data = $serializer->dataSerialize($views, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/hours/num_recorded.{_format}", defaults={"_format"="json"}, requirements={"_format"="json|xml"})
     * @Route("/hours/recorded.{_format}", defaults={"_format"="json"}, requirements={"_format"="json|xml"})
     */
    public function hoursRecordedAction(Request $request, StatsService $recordsService, SerializerService $serializer)
    {
        [$criteria, $sort, $fromDate, $toDate, $limit, $page] = $this->processRequestData($request);

        $groupBy = $request->get('group_by') ?: 'month';

        $views = $recordsService->getHoursRecordedGroupedBy($fromDate, $toDate, $limit, $page, $criteria, $sort, $groupBy);

        $views = [
            'limit' => $limit,
            'page' => $page,
            'criteria' => $criteria,
            'sort' => $sort,
            'group_by' => $groupBy,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'views' => $views,
        ];

        $data = $serializer->dataSerialize($views, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/mmobj/stats.{_format}", defaults={"_format"="json"}, requirements={"_format"="json|xml"})
     *
     * TODO: add criteria??? (see processRequestData)
     */
    public function globalStatsAction(Request $request, StatsService $recordsService, SerializerService $serializer)
    {
        $groupBy = $request->get('group_by') ?: 'month';

        $stats = $recordsService->getGlobalStats($groupBy);

        $stats = [
            'group_by' => $groupBy,
            'stats' => $stats,
        ];

        $data = $serializer->dataSerialize($stats, $request->getRequestFormat());

        return new Response($data);
    }

    protected function processRequestData(Request $request)
    {
        $MAX_LIMIT = 500;
        //Request variables.
        $criteria = $request->get('criteria') ?: [];
        $sort = (int) ($request->get('sort'));
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $limit = (int) ($request->get('limit'));
        $page = $request->get('page') ?: 0;

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
        $fromDate = \DateTime::createFromFormat('Y-m-d\TH:i:s', $fromDate);
        $toDate = \DateTime::createFromFormat('Y-m-d\TH:i:s', $toDate);
        if (!$fromDate) {
            $fromDate = new \DateTime('Z');
            $fromDate->setTime(0, 0, 0);
        }
        if (!$toDate) {
            $toDate = new \DateTime('Z');
        }

        return [$criteria, $sort, $fromDate, $toDate, $limit, $page];
    }
}
