<?php

namespace Pumukit\BaseLivePlayerBundle\Controller;

use JMS\Serializer\Serializer;
use Pumukit\BaseLivePlayerBundle\Services\APIService;
use Symfony\Component\Routing\Annotation\Route;
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
        if (!$limit || $limit > 100) {
            $limit = 100;
        }

        return [
            $criteria = $request->get('criteria') ?: [],
            $sort = $request->get('sort') ?: [],
            $limit,
        ];
    }
}
