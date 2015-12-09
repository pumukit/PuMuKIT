<?php

namespace Pumukit\SchemaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/api/media")
 */
class APIRecordedController extends Controller
{
    /**
     * @Route("/mmobj/num_recorded.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     * @Route("/mmobj/recorded.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function mmobjRecordedAction(Request $request)
    {
        $mmRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
        $seriesRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:series');
        $liveRepo = $this->get('doctrine_mongodb')->getRepository('PumukitLiveBundle:Live');

        $viewsRepo = $this->get('doctrine_mongodb')->getRepository('PumukitStatsBundle:ViewsLog');
        $serializer = $this->get('serializer');
        $recordsService = $this->get('pumukit_stats.stats');

        list($criteria, $sort, $fromDate, $toDate, $limit, $page) = $this->processRequestData($request);

        $groupBy = $request->get('group_by') ?: 'month';

        $views = $recordsService->getMmobjRecordedGroupedBy($fromDate, $toDate, $limit, $page, $criteria, $sort, $groupBy);

        $views = array(
            'limit' => $limit,
            'page' => $page,
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

    protected function processRequestData(Request $request)
    {
        $MAX_LIMIT = 500;
        //Request variables.
        $criteria = $request->get('criteria') ?: array();
        $sort = intval($request->get('sort'));
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $limit = intval($request->get('limit'));
        $page = $request->get('page') ?: 0;

        //Processing variables.
        if (!$limit || $limit > $MAX_LIMIT) {
            $limit = $MAX_LIMIT;
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
