<?php

namespace Pumukit\EncoderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/encoder")
 */
class APIController extends Controller
{
    /**
     * @Route("/profiles.{_format}", defaults={"_format"="json"}, requirements={"_format": "json"})
     */
    public function profilesAction()
    {
        $profiles = $this->get('pumukitencoder.profile')->getProfiles();

        return new JsonResponse($profiles);
    }

    /**
     * @Route("/cpus.{_format}", defaults={"_format"="json"}, requirements={"_format": "json"})
     */
    public function cpusAction()
    {
        $cpus = $this->get('pumukitencoder.cpu')->getCpus();

        return new JsonResponse($cpus);
    }

    /**
     * @Route("/jobs.{_format}", defaults={"_format"="json"}, requirements={"_format": "json"})
     */
    public function jobsAction()
    {
        $jobService = $this->get('pumukitencoder.job');
        $stats = $jobService->getAllJobsStatus();

        return new JsonResponse($stats);
    }
}
