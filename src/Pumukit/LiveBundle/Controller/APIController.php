<?php

namespace Pumukit\LiveBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Pumukit\LiveBundle\Document\Event;
use Pumukit\LiveBundle\Document\Live;

/**
 * @Route("/api/live")
 */
class APIController extends Controller
{
    /**
     * @Route("/events.{_format}", defaults={"_format"="json"}, requirements={"_format": "json"})
     */
    public function eventsAction(Request $request)
    {
        $eventRepo = $this->get('doctrine_mongodb')->getRepository(Event::class);
        $serializer = $this->get('jms_serializer');

        $limit = $request->get('limit');
        if (!$limit || $limit > 100) {
            $limit = 100;
        }

        $criteria = $request->get('criteria') ?: [];
        $sort = $request->get('sort') ?: [];

        $qb = $eventRepo->createQueryBuilder();

        if ($criteria) {
            $qb = $qb->addAnd($criteria);
        }

        $qb_series = clone $qb;
        $qb_series = $qb_series->limit($limit)
                               ->sort($sort);

        $qb_event = clone $qb;

        $total = $qb->count()->getQuery()->execute();
        $event = $qb_event->getQuery()->execute()->toArray();

        $counts = ['total' => $total,
                        'limit' => $limit,
                        'criteria' => $criteria,
                        'sort' => $sort,
                        'event' => $event, ];

        $data = $serializer->serialize($counts, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/lives.{_format}", defaults={"_format"="json"}, requirements={"_format": "json"})
     */
    public function livessAction(Request $request)
    {
        $liveRepo = $this->get('doctrine_mongodb')->getRepository(Live::class);
        $serializer = $this->get('jms_serializer');

        $limit = $request->get('limit');
        if (!$limit || $limit > 100) {
            $limit = 100;
        }

        $criteria = $request->get('criteria') ?: [];
        $sort = $request->get('sort') ?: [];

        $qb = $liveRepo->createQueryBuilder();

        if ($criteria) {
            $qb = $qb->addAnd($criteria);
        }

        $qb_series = clone $qb;
        $qb_series = $qb_series->limit($limit)
                               ->sort($sort);

        $qb_live = clone $qb;

        $total = $qb->count()->getQuery()->execute();
        $live = $qb_live->getQuery()->execute()->toArray();

        $counts = ['total' => $total,
                        'limit' => $limit,
                        'criteria' => $criteria,
                        'sort' => $sort,
                        'live' => $live, ];

        $data = $serializer->serialize($counts, $request->getRequestFormat());

        return new Response($data);
    }
}
