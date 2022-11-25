<?php

declare(strict_types=1);

namespace Pumukit\BaseLivePlayerBundle\Controller;

use Pumukit\BaseLivePlayerBundle\Services\APIService;
use Pumukit\CoreBundle\Services\SerializerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/live")
 */
class APIController extends AbstractController
{
    private $APIService;
    private $serializer;

    public function __construct(APIService $APIService, SerializerService $serializer)
    {
        $this->APIService = $APIService;
        $this->serializer = $serializer;
    }

    /**
     * @Route("/events.{_format}", defaults={"_format"="json"}, requirements={"_format"="json"})
     */
    public function eventsAction(Request $request): Response
    {
        [$criteria, $sort, $limit] = $this->getParameters($request);

        $counts = $this->APIService->getEventsByCriteria($criteria, $sort, $limit);

        $data = $this->serializer->dataSerialize($counts, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/lives.{_format}", defaults={"_format"="json"}, requirements={"_format"="json"})
     */
    public function livesAction(Request $request): Response
    {
        [$criteria, $sort, $limit] = $this->getParameters($request);

        $counts = $this->APIService->getLivesByCriteria($criteria, $sort, $limit);

        $data = $this->serializer->dataSerialize($counts, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/live_events.{_format}", defaults={"_format"="json"}, requirements={"_format": "json"})
     */
    public function liveEventsAction(Request $request): Response
    {
        [$criteria, $sort, $limit] = $this->getParameters($request);

        $counts = $this->APIService->getLiveEventsByCriteria($criteria, $sort, $limit);

        $data = $this->serializer->dataSerialize($counts, $request->getRequestFormat());

        return new Response($data);
    }

    private function getParameters(Request $request)
    {
        $limit = $request->get('limit');
        $sort = $request->get('sort') ?: [];

        if (!$limit || $limit > 100) {
            $limit = 100;
        }

        try {
            $criteria_type = $this->getCriteria($request->get('criteria'), $request->get('criteriajson'));
        } catch (\Exception $e) {
            $error = ['error' => sprintf('Invalid criteria (%s)', $e->getMessage())];
            $data = $this->serializer->dataSerialize($error, $request->getRequestFormat());

            return new Response($data, 400);
        }

        return [
            $criteria_type ?: [],
            $sort,
            $limit,
        ];
    }

    private function getCriteria($row, $json)
    {
        if ($json) {
            $criteria = json_decode($json, true);

            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new \InvalidArgumentException(json_last_error_msg());
            }
        } else {
            $criteria = (array) $row;
        }

        return $criteria;
    }
}
