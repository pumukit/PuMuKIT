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
    protected $statsService;
    protected $serializer;

    public function __construct(StatsService $statsService, SerializerService $serializer)
    {
        $this->statsService = $statsService;
        $this->serializer = $serializer;
    }

    /**
     * @Route("/mmobj/num_recorded.{_format}", defaults={"_format"="json"}, requirements={"_format"="json|xml"})
     * @Route("/mmobj/recorded.{_format}", defaults={"_format"="json"}, requirements={"_format"="json|xml"})
     */
    public function mmobjRecordedAction(Request $request)
    {
        [$criteria, $sort, $fromDate, $toDate, $limit, $page] = $this->processRequestData($request);

        $groupBy = $request->get('group_by') ?: 'month';

        $views = $this->statsService->getMmobjRecordedGroupedBy($fromDate, $toDate, $limit, $page, $criteria, $sort, $groupBy);

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

        $data = $this->serializer->dataSerialize($views, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/series/num_recorded.{_format}", defaults={"_format"="json"}, requirements={"_format"="json|xml"})
     * @Route("/series/recorded.{_format}", defaults={"_format"="json"}, requirements={"_format"="json|xml"})
     * @Route("/series/published.{_format}", defaults={"_format"="json"}, requirements={"_format"="json|xml"})
     */
    public function seriesRecordedAction(Request $request)
    {
        [$criteria, $sort, $fromDate, $toDate, $limit, $page] = $this->processRequestData($request);

        $groupBy = $request->get('group_by') ?: 'month';

        $views = $this->statsService->getSeriesRecordedGroupedBy($fromDate, $toDate, $limit, $page, $criteria, $sort, $groupBy);

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

        $data = $this->serializer->dataSerialize($views, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/hours/num_recorded.{_format}", defaults={"_format"="json"}, requirements={"_format"="json|xml"})
     * @Route("/hours/recorded.{_format}", defaults={"_format"="json"}, requirements={"_format"="json|xml"})
     */
    public function hoursRecordedAction(Request $request)
    {
        [$criteria, $sort, $fromDate, $toDate, $limit, $page] = $this->processRequestData($request);

        $groupBy = $request->get('group_by') ?: 'month';

        $views = $this->statsService->getHoursRecordedGroupedBy($fromDate, $toDate, $limit, $page, $criteria, $sort, $groupBy);

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

        $data = $this->serializer->dataSerialize($views, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/mmobj/stats.{_format}", defaults={"_format"="json"}, requirements={"_format"="json|xml"})
     */
    public function globalStatsAction(Request $request)
    {
        $groupBy = $request->get('group_by') ?: 'month';

        $stats = $this->statsService->getGlobalStats($groupBy);

        $stats = [
            'group_by' => $groupBy,
            'stats' => $stats,
        ];

        $data = $this->serializer->dataSerialize($stats, $request->getRequestFormat());

        return new Response($data);
    }

    protected function processRequestData(Request $request)
    {
        $MAX_LIMIT = 1500;
        //Request variables.
        $criteria = $request->get('criteria') ?: [];
        $sort = (int) ($request->get('sort'));
        $fromDate = $request->get('from_date') ?? date('Y-m-d');
        $toDate = $request->get('to_date') ?? date('Y-m-d');
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
