<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Controller;

use Pumukit\EncoderBundle\Services\CpuService;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\EncoderBundle\Services\ProfileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/encoder")
 */
class APIController extends AbstractController
{
    /**
     * @Route("/profiles.{_format}", defaults={"_format"="json"}, requirements={"_format"="json"})
     */
    public function profilesAction(ProfileService $profileService): JsonResponse
    {
        $profiles = $profileService->getProfiles();

        return new JsonResponse($profiles);
    }

    /**
     * @Route("/cpus.{_format}", defaults={"_format"="json"}, requirements={"_format"="json"})
     */
    public function cpusAction(CpuService $cpuService): JsonResponse
    {
        $cpus = $cpuService->getCpus();

        return new JsonResponse($cpus);
    }

    /**
     * @Route("/jobs.{_format}", defaults={"_format"="json"}, requirements={"_format"="json"})
     */
    public function jobsAction(JobService $jobService): JsonResponse
    {
        $stats = $jobService->getAllJobsStatus();

        return new JsonResponse($stats);
    }
}
