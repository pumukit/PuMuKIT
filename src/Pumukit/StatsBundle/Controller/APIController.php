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

    /**
     * @Route("/mmobj/most_viewed.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function mmobjMostViewedAction(Request $request)
    {
        $viewsRepo = $this->get('doctrine_mongodb')->getRepository('PumukitStatsBundle:ViewsLog');
        $serializer = $this->get('serializer');
        $viewsService = $this->get('pumukit_stats.stats');

        $limit = intval($request->get('limit'));
        if(!$limit || $limit > 100 ) {
            $limit = 100;
        }

        $criteria = $request->get('criteria')?:array();
        $sort = intval($request->get('sort'));
        if(!in_array($sort, array(1,-1))) {
            $sort = -1;
        }
        
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        if(!strpos($fromDate,'T')) {
            $fromDate .= "T00:00:00Z";
        }
        if(!strpos($toDate,'T')) {
            $toDate .= "T23:59:59Z";
        }
        $fromDate = \DateTime::createFromFormat('Y-m-d\TH:i:sZ', $fromDate);
        $toDate = \DateTime::createFromFormat('Y-m-d\TH:i:sZ', $toDate);

        if(!$fromDate) {
            $fromDate = null;
        }
        if(!$toDate) {
            $toDate = null;
        }

        $mmobjs = $viewsService->getMmobjsMostViewedByRange($fromDate, $toDate, $limit, $criteria, $sort);

        $views = array(
            'limit' => $limit,
            'criteria' => $criteria, 
            'sort'  => $sort,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'mmobjs' => $mmobjs
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

        $limit = intval($request->get('limit'));
        if(!$limit || $limit > 100 ) {
            $limit = 100;
        }

        $criteria = $request->get('criteria')?:array();
        $sort = intval($request->get('sort'));
        if(!in_array($sort, array(1,-1))) {
            $sort = -1;
        }
        
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        if(!strpos($fromDate,'T')) {
            $fromDate .= "T00:00:00";
        }
        if(!strpos($toDate,'T')) {
            $toDate .= "T23:59:59";
        }
        $fromDate = \DateTime::createFromFormat('Y-m-d\TH:i:s', $fromDate);
        $toDate = \DateTime::createFromFormat('Y-m-d\TH:i:s', $toDate);

        if(!$fromDate) {
            $fromDate = null;
        }
        if(!$toDate) {
            $toDate = null;
        }

        $series = $viewsService->getSeriesMostViewedByRange($fromDate, $toDate, $limit, $criteria, $sort);

        $views = array(
            'limit' => $limit,
            'criteria' => $criteria, 
            'sort'  => $sort,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'series' => $series
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

        $limit = intval($request->get('limit'));
        if(!$limit || $limit > 100 ) {
            $limit = 100;
        }

        $criteria = $request->get('criteria')?:array();
        $sort = intval($request->get('sort'));
        if(!in_array($sort, array(1,-1))) {
            $sort = -1;
        }
        
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        if(!strpos($fromDate,'T')) {
            $fromDate .= "T00:00:00";
        }
        if(!strpos($toDate,'T')) {
            $toDate .= "T23:59:59";
        }
        $fromDate = \DateTime::createFromFormat('Y-m-d\TH:i:s', $fromDate);
        $toDate = \DateTime::createFromFormat('Y-m-d\TH:i:s', $toDate);

        if(!$fromDate) {
            $fromDate = new \DateTime('Z');
            $fromDate->setTime(0,0,0);
        }
        if(!$toDate) {
            $toDate = new \DateTime('Z');
        }

        $groupBy = $request->get('group_by');

        $views = $viewsService->getTotalViewedGrouped($fromDate, $toDate, $limit, $criteria, $sort, $groupBy);

        $views = array(
            'limit' => $limit,
            'criteria' => $criteria, 
            'sort'  => $sort,
            'group_by' => $groupBy,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'views' => $views
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

        $limit = intval($request->get('limit'));
        if(!$limit || $limit > 100 ) {
            $limit = 100;
        }

        $criteria = $request->get('criteria')?:array();
        $sort = intval($request->get('sort'));
        if(!in_array($sort, array(1,-1))) {
            $sort = -1;
        }
        
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        if(!strpos($fromDate,'T')) {
            $fromDate .= "T00:00:00";
        }
        if(!strpos($toDate,'T')) {
            $toDate .= "T23:59:59";
        }
        $fromDate = \DateTime::createFromFormat('Y-m-d\TH:i:s', $fromDate);
        $toDate = \DateTime::createFromFormat('Y-m-d\TH:i:s', $toDate);

        if(!$fromDate) {
            $fromDate = new \DateTime('Z');
            $fromDate->setTime(0,0,0);
        }
        if(!$toDate) {
            $toDate = new \DateTime('Z');
        }

        $groupBy = $request->get('group_by');

        $views = $viewsService->getTotalViewedGroupedByMmobj(new \MongoId($mmobjId), $fromDate, $toDate, $limit, $criteria, $sort, $groupBy);

        $views = array(
            'limit' => $limit,
            'criteria' => $criteria, 
            'sort'  => $sort,
            'group_by' => $groupBy,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'views' => $views
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

        $limit = intval($request->get('limit'));
        if(!$limit || $limit > 100 ) {
            $limit = 100;
        }

        $criteria = $request->get('criteria')?:array();
        $sort = intval($request->get('sort'));
        if(!in_array($sort, array(1,-1))) {
            $sort = -1;
        }
        
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        if(!strpos($fromDate,'T')) {
            $fromDate .= "T00:00:00Z";
        }
        if(!strpos($toDate,'T')) {
            $toDate .= "T23:59:59Z";
        }
        $fromDate = \DateTime::createFromFormat('Y-m-d\TH:i:se', $fromDate);
        $toDate = \DateTime::createFromFormat('Y-m-d\TH:i:se', $toDate);

        if(!$fromDate) {
            $fromDate = new \DateTime('Z');
            $fromDate->setTime(0,0,0);
        }
        if(!$toDate) {
            $toDate = new \DateTime('Z');
        }


        $groupBy = $request->get('group_by');

        $views = $viewsService->getTotalViewedGroupedBySeries(new \MongoId($seriesId), $fromDate, $toDate, $limit, $criteria, $sort, $groupBy);

        $views = array(
            'limit' => $limit,
            'criteria' => $criteria, 
            'sort'  => $sort,
            'group_by' => $groupBy,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'views' => $views
        );

        $data = $serializer->serialize($views, $request->getRequestFormat());
        return new Response($data);
    }

}
