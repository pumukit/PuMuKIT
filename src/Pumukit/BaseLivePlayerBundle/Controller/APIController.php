<?php

namespace Pumukit\BaseLivePlayerBundle\Controller;

use JMS\Serializer\Serializer;
use Pumukit\BaseLivePlayerBundle\Services\APIService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/api/live")
 */
class APIController extends Controller
{
    /**
     * @Route("/events.{_format}", defaults={"_format"="json"}, requirements={"_format": "json"})
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function eventsAction(Request $request): Response
    {
        [$criteria, $sort, $limit] = $this->getParameters($request);

        /** @var APIService */
        $apiService = $this->get('pumukit_base_live_player.api');

        $counts = $apiService->getEventsByCriteria($criteria, $sort, $limit);

        $data = $this->dataSerialize($counts, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/lives.{_format}", defaults={"_format"="json"}, requirements={"_format": "json"})
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function livesAction(Request $request): Response
    {
        [$criteria, $sort, $limit] = $this->getParameters($request);

        /** @var APIService */
        $apiService = $this->get('pumukit_base_live_player.api');

        $counts = $apiService->getLivesByCriteria($criteria, $sort, $limit);

        $data = $this->dataSerialize($counts, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/live_events.{_format}", defaults={"_format"="json"}, requirements={"_format": "json"})
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function liveEventsAction(Request $request): Response
    {
        [$criteria, $sort, $limit] = $this->getParameters($request);

        /** @var APIService */
        $apiService = $this->get('pumukit_base_live_player.api');

        $counts = $apiService->getLiveEventsByCriteria($criteria, $sort, $limit);

        $data = $this->dataSerialize($counts, $request->getRequestFormat());

        return new Response($data);
    }

    private function dataSerialize(array $counts, string $requestFormat = null): string
    {
        /** @var Serializer */
        $serializer = $this->get('jms_serializer');

        if (!$requestFormat) {
            $requestFormat = 'html';
        }

        return $serializer->serialize($counts, $requestFormat);
    }

    private function getParameters(Request $request): array
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
            $data = $serializer->serialize($error, $request->getRequestFormat());

            return new Response($data, 400);
        }

        return [
            $criteria = $criteria_type ?: [],
            $sort,
            $limit,
        ];
    }

    /**
     * Get criteria to filter objects from the requets.
     *
     * JSON criteria has priority over row criteria.
     *
     * @param array|string $row
     * @param string       $json
     *
     * @return array
     */
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
