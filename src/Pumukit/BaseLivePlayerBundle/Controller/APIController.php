<?php

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
    /**
     * @Route("/events.{_format}", defaults={"_format"="json"}, requirements={"_format": "json"})
     */
    public function eventsAction(Request $request, APIService $APIService, SerializerService $serializer): Response
    {
        [$criteria, $sort, $limit] = $this->getParameters($request);

        $counts = $APIService->getEventsByCriteria($criteria, $sort, $limit);

        $data = $serializer->dataSerialize($counts, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/lives.{_format}", defaults={"_format"="json"}, requirements={"_format": "json"})
     */
    public function livesAction(Request $request, APIService $APIService, SerializerService $serializer): Response
    {
        [$criteria, $sort, $limit] = $this->getParameters($request);

        $counts = $APIService->getLivesByCriteria($criteria, $sort, $limit);

        $data = $serializer->dataSerialize($counts, $request->getRequestFormat());

        return new Response($data);
    }

    private function getParameters(Request $request): array
    {
        $limit = $request->get('limit');
        if (!$limit || $limit > 100) {
            $limit = 100;
        }

        return [
            $criteria = $request->get('criteria') ?: [],
            $sort = $request->get('sort') ?: '',
            $limit,
        ];
    }
}
